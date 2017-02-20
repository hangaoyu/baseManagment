<?php
/**
 * PayService.php
 * Date: 2016/11/30
 */

namespace App\Services;

use App\Models\CarInsurance\Freeze;
use App\Models\CarInsurance\Installment;
use App\Models\CarInsurance\Order;
use App\Models\CarInsurance\PaymentLog;
use Log;

class PayService
{
    //static $supsendTime = config('carInsurance.suspendTime');
    /**
     * 发起支付请求，顺便把返回值包装成需要插入的log数组
     * @param $order Order
     * @param $installment Installment
     * @return array
     */
    public static function startPay($order, $installment)
    {
        try{
            $createRet = self::createFreezeForPay($order);
            if($createRet['retCode']!='0000'){ //预冻结生成失败
                return "预冻结生成失败,请重试";
            }

            //生成预冻结表数据,被提前到了这里
            $params = ['order_id'=>$order->id, 'money'=>$createRet['amount'], 'freeze_queryid'=> $createRet['queryId'], 'datetime'=>date('Y-m-d h:i:s', time())];
            \Log::info("问题处在这里");
            $order->getFreeze()->create($params);
            //Freeze::store($params);
            \Log::info("果然");
            $chargeRet = self::realTimeCharge($order);
            if($chargeRet['retCode']!='0000') { //扣款失败
                \Log::info("订单: {{ $order->id }} 支付请求失败了!");
                self::cancelFreezeForPay($order, $createRet['queryId']); //取消预冻结生成操作
                $order->errorCount();
                return "扣款失败，请重试";
            }

            //生成预冻结表数据,被提前了...
            //$params = ['order_id'=>$order->id, 'money'=>$createRet['amount'], 'freeze_queryid'=> $createRet['queryId'], 'datetime'=>date('Y-m-d h:i:s', time())];
            //Freeze::store($params);
        }catch (\Exception $e){
            //todo 到了这步该怎么办 ?
            return $e->getMessage();
        }

        return "支付成功";
    }

    /**
     * @param $type array
     * @param $orderId integer
     * @param $ret array
     */
    public static function createLogParams($type, $orderId, $ret,$reqData=null, $installmentId=null)
    {
        $ret['order_id'] = $orderId;
        $ret['order_num'] = $ret['orderNum'] = $reqData['project_orderNo'];
        $ret['processTime'] = isset($ret['processDate']) ? $ret['processDate']: date('Y-m-d h:i:s', time());
        $ret['request_data'] = json_encode($reqData);
        if($ret['retCode']!='0000'){
            $ret['orderStatus']= 3; //失败了
        }
        if(in_array($type, ['createFreeze', 'cancelFreeze'])){
            $ret['freeze_time'] = $ret['processTime'];
            $ret['freeze_money'] = $reqData['amount'] / 100;
            $ret['freeze_queryId'] = isset($ret['queryId']) ? $ret['queryId']: '';
           // unset($ret['order_num']); //非扣款操作是否需要订单号，应该不需要! Add 看来还是需要的
        }
        if($type=='createFreeze') {
            $ret['status'] = 0;
            if($ret['retCode']=='0000'){
                $ret['retDesc'] = '预冻结生成成功';
            }
        }
        if($type == 'realTimeCharge') {
            $ret['status'] = 3;
            $ret['installment_id'] = $installmentId;
            if($ret['retCode']== '0000') {
                $ret['retDesc'] = "支付完成";
            }
        }
        if($type == 'cancelFreeze') {
            $ret['status'] = 2;
            if($ret['retCode']=='0000') {
                $ret['retDesc'] = '由于实时扣款失败,导致预冻结生成已被撤销';
            }
        }
        unset($ret['processDate'], $ret['project_orderNo']); //有没有都去除了
        return $ret;
    }

    /**
     * 生成相应的请求参数
     * @param $type string
     * @param $order Order
     * @param $queryid string
     */
    public static function createData($type, $order, $queryid=null, $projectNo='')
    {
        $data = ['projectNo'=> $projectNo];
        $data['project_orderNo'] = guid();
        $isTest = config('carInsurance.test');
       // $cardNo= '';
        //$accName = '';
        //$cvv = '';
       // $expire = '';
        if($isTest) {
            $data['istest'] = 1; //测试
            $data['cardNo'] = '5309900599078556';
            $data['accName'] = "王武";
            $cvv = '214';
            $expire = '1502';
        } else {
            if($order->cardInfo==null) {
                throw new \Exception("用户银行卡信息未填写,错误!");
            }
            $data['istest'] = 0;// 正式
            $data['cardNo'] = $order->cardInfo->card;
            $data['accName'] = $order->cardInfo->name;
            $arr = explode("/",$order->cardInfo->card_validity); //'02/16'
           // throw new \Exception($order->cardInfo->cvn_number);
            //if(!isset($arr[1]))
            $expire = $arr[1].$arr[0];//explode("/",$order->cardInfo->cvn_number); //'02/16'
            $cvv = $order->cardInfo->cvn_number;
            $data['mobile'] = $order->cardInfo->phone_number;
            $data['accId'] = $order->cardInfo->id_number;
            //$cvv =
        }
        //$data['cardNo'] = $order->cardInfo ? $order->cardInfo->card: '5309900599078556';
        //$data['accName'] = $order->cardInfo? $order->cardInfo->name: "王武";
        //if(emp$order->installment)
        $installment = $order->installment()->orderBy('opertiontime', 'asc')->take(1)->get();
        if($installment->isEmpty()) {
            throw new \Exception("未获取到分期信息,错误!");
        }
        $first = $installment[0]; //获取一个分期以操作

        $data['cvv'] = $cvv;
        $data['expire'] = $expire;
        //$dat
        if($type=='realTimeCharge'){
            $data['amount'] = $first->money * 100; //将元转为分为单位
            //$data['cardNo'] = $order->cardInfo? $order->cardInfo->card: '38475834538457384753845935';

        }else if($type=='createFreeze'){
           // $data['cardNo'] = $order->cardInfo? $order->cardInfo->card: '38475834538457384753845935';
            $data['amount'] = ($order->amount-1) * $first->money * 100; //转为分为单位
//            $data['cvv'] = $cvv;
//            $data['expire'] = $expire;
            /*if($isTest){
                $data['cvv'] = '214';
                $data['expire'] = '1502';
            } else {
                $data['cvv'] = $order->cardInfo->cvn_number;
                $data['expire'] = $order->cardInfo->card_validity;
            }*/

        }else if($type=='cancelFreeze') {
            $data['amount'] = ($order->amount-1) * $first->money * 100; //转为分为单位
            $data['queryId'] = $queryid;
            unset($data['cvv'], $data['expire'], $data['accId'], $data['mobile']);

        }
        return $data;
    }
    /**
     * 发起预冻结生成请求
     * @param $order Order
     * @return  array
     */
    public static function createFreezeForPay($order)
    {
        $url = $order->getPreFreezeUrl();
        //$url = config('carInsurance.pay.createFreeze');
        //$url = "http://pay.local.jojin.com/api/v1/JieLan/PreFreeze";
        $data = self::createData('createFreeze', $order, '', $order->getProjectNo());
        $ret = self::doRequest($url, $data);
        $logParam = self::createLogParams('createFreeze', $order->id, $ret, $data);
        $order->getLogger()->store($logParam); //无论成功失败，存储这条数据
        //PaymentLog::store($logParam);
        $ret['amount'] = $data['amount'] / 100; //把amount参数带出来,还原为元为单位
        return $ret;
    }

    /**
     * @param $order Order
     * @return array
     */
    public static function realTimeCharge($order)
    {
        $uri = $order->getChargeUrl();
       // $uri = config('carInsurance.pay.charge');
        //$uri = "http://pay.local.jojin.com/api/v1/JieLan/RealtimeCharge";
        $data = self::createData('realTimeCharge', $order, '', $order->getProjectNo());
        $ret = self::doRequest($uri, $data);
        $installment = $order->installment()->orderBy('opertiontime', 'asc')->take(1)->get()[0];
        //$installmentId = $installment->id;
        $logParam = self::createLogParams('realTimeCharge', $order->id, $ret, $data, $installment->id);
        $order->getLogger()->store($logParam);
        //PaymentLog::store($logParam);
        return $ret;
    }

    /**
     * @param $order Order
     * @param $queryId int
     * @return array
     */
    public static function cancelFreezeForPay($order, $queryId)
    {
        $url = $order->getCancelUrl();
        //$url = config('carInsurance.pay.cancelFreeze');
        //$url = "http://pay.local.jojin.com/api/v1/JieLan/PreFreezeCancel";
        $data = self::createData('cancelFreeze', $order, $queryId, $order->getProjectNo());
        $ret = self::doRequest($url, $data);
        //$ret['queryId'] = $queryId;//需要的冻结流水id参数
        $logParam = self::createLogParams('cancelFreeze', $order->id, $ret, $data);
        $order->getLogger()->store($logParam);
        //PaymentLog::store($logParam);
        return $ret;
    }

    /**
     * 发起url请求
     * @param $url string
     * @param $data array
     * @return array
     */
    public static function doRequest($url, $data)
    {
        //添加请求间隔时间
        $delayTime = config('carInsurance.delay');
        sleep($delayTime); //以秒为单位的延迟
        //
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $ret = curl_exec($curl);
        curl_close($curl);
        return json_decode($ret, true);
    }
}