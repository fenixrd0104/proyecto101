<?php
namespace Payment\Charge\Wx;
use GuzzleHttp\Client;
use Payment\Common\Weixin\Data\Charge\BarChargeData;
use Payment\Common\Weixin\WxBaseStrategy;
use Payment\Common\WxConfig;
use Payment\Utils\DataParser;
use Payment\Common\PayException;
/**
 * @author: helei
 * @createTime: 2017-03-06 18:29
 * @description: 微信 刷卡支付  对应支付宝的条码支付
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 */
class WxBarCharge extends WxBaseStrategy
{
    protected $reqUrl = 'https://api.mch.weixin.qq.com/{debug}/pay/micropay';

    protected $unknownErrType=[
       'SYSTEMERROR',
       'BANKERROR',
       'USERPAYING',
    ];

    public function getBuildDataClass()
    {
        return BarChargeData::class;
    }

    protected function sendReq($xml)
    {
        $url = $this->reqUrl;
        if (is_null($url)) {
            throw new PayException('目前不支持该接口。请联系开发者添加');
        }

        if ($this->config->useSandbox) {
            $url = str_ireplace('{debug}', WxConfig::SANDBOX_PRE, $url);
        } else {
            $url = str_ireplace('{debug}/', '', $url);
        }

        $client = new Client([
            'timeout' => '30.0'
        ]);
        // @note: 微信部分接口并不需要证书支持。这里为了统一，全部携带证书进行请求
        $options = [
            'body' => $xml,
            'cert' => $this->config->appCertPem,
            'ssl_key' => $this->config->appKeyPem,
            'verify' => $this->config->cacertPath,
            'http_errors' => false
        ];
        $response = $client->request('POST', $url, $options);
        if ($response->getStatusCode() != '200') {
            throw new PayException('网络发生错误，请稍后再试curl返回码：' . $response->getReasonPhrase());
        }

        $body = $response->getBody()->getContents();
        // 格式化为数组
        $retData = DataParser::toArray($body);
        if (strtoupper($retData['return_code']) != 'SUCCESS') {
            throw new PayException('微信返回错误提示：' . $retData['return_msg']);
        }

        if (strtoupper($retData['result_code']) != 'SUCCESS' && !in_array($retData['err_code'],$this->unknownErrType) ) {
            $msg = $retData['err_code_des'] ? $retData['err_code_des'] : $retData['err_msg'];
            throw new PayException('微信返回错误提示：' . $msg);
        }
        return $retData;
    }
    /**
     * 返回的数据
     * @param array $ret
     * @return array
     */
    protected function retData(array $ret)
    {
       if(strtoupper($ret['result_code']) == 'SUCCESS'){
           $ret['total_fee'] = bcdiv($ret['total_fee'], 100, 2);
           $ret['cash_fee'] = bcdiv($ret['cash_fee'], 100, 2);
       }

        if ($this->config->returnRaw) {
            return $ret;
        }

        return $ret;
    }
}
