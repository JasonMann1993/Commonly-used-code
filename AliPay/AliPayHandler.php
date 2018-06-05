<?php

namespace App\Http\Helpers;

use App\Http\Helpers\Aop\AopClient;
use App\Http\Helpers\Aop\request\AlipayTradePagePayRequest;
use App\Http\Helpers\Aop\request\AlipayTradeRefundRequest;

class AliPayHandler
{

    public $aop;
    /**
     * AliPayHandler constructor.
     */
    public function __construct()
    {
        $this->aop = new AopClient();
        $this->aop->appId = config('alipay.app_id');
        $this->aop->rsaPrivateKey = config('alipay.app_private_key');
        $this->aop->alipayrsaPublicKey = config('alipay.ali_public_key');
        $this->aop->apiVersion = '1.0';
        $this->aop->signType = 'RSA';
        $this->aop->postCharset='utf-8';
        $this->aop->format='json';
    }

    /**
     * 网页支付
     * @param $content
     * @return Aop\提交表单HTML文本|string
     */
    public function pagePay($content)
    {
        $request = new AlipayTradePagePayRequest();
        $request->setReturnUrl(route('ali.return_url'));
        $request->setNotifyUrl(route('ali.notify'));
        $request->setBizContent(json_encode($content));

        $result = $this->aop->pageExecute ($request);

        return $result;
    }

    public function rsaCheckV1($params, $rsaPublicKeyFilePath,$signType='RSA')
    {
        return $this->aop->rsaCheckV1($params, $rsaPublicKeyFilePath,$signType);
    }

    public function refund($user_order, $refund_money, $refund_no)
    {
        $request = new AlipayTradeRefundRequest();

        $bizcontent = json_encode([
            'out_trade_no'=>$user_order['trade_no'],
            'refund_amount'=> $refund_money,
            'refund_reason'=>'正常退款',
            'out_request_no'=> $refund_no,
        ]);

        $request->setBizContent($bizcontent);
        $result = $this->aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            return [
                'code' => '200',
                'msg' => '退款成功'
            ];
        } else {
            return  [
                'code' => '500',
                'msg' => $result->$responseNode->sub_msg
            ];
        }
    }

}