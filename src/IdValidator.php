<?php

namespace Jxlwqq\IdValidator;

/**
 * Class IdValidator.
 */
class IdValidator
{
    use Helper, Generator, Checker;

    private $_addressCodeList = []; // 现行地址码数据
    private $_abandonedAddressCodeList = []; // 废弃地址码数据

    /**
     * IdValidator constructor.
     */
    public function __construct()
    {
        $this->_addressCodeList = include __DIR__.'/../data/addressCode.php';
        $this->_abandonedAddressCodeList = include __DIR__.'/../data/abandonedAddressCode.php';
    }

    /**
     * 验证身份证号合法性.
     *
     * @param string $id 身份证号
     *
     * @return bool
     */
    public function isValid($id)
    {
        // 基础验证
        $code = $this->_checkIdArgument($id);
        if (empty($code)) {
            return false;
        }

        // 分别验证：*地址码*、*出生日期码*和*顺序码*
        if (!$this->_checkAddressCode($code['addressCode']) || !$this->_checkBirthdayCode($code['birthdayCode']) || !$this->_checkOrderCode($code['order'])) {
            return false;
        }

        // 15位身份证不含校验码
        if ($code['type'] === 15) {
            return true;
        }

        // 验证：校验码
        $checkBit = $this->_generatorCheckBit($code['body']);

        // 检查校验码
        return $checkBit == $code['checkBit'];
    }

    /**
     * 获取身份证信息.
     *
     * @param string $id 身份证号
     *
     * @return array|bool
     */
    public function getInfo($id)
    {
        // 验证有效性
        if ($this->isValid($id) === false) {
            return false;
        }
        $code = $this->_checkIdArgument($id);
        $addressInfo = $this->_getAddressInfo($code['addressCode']);

        return [
            'addressCode'   => $code['addressCode'],
            'abandoned'     => isset($this->_abandonedAddressCodeList[$code['addressCode']]) ? 1 : 0,
            'address'       => is_array($addressInfo) ? implode($addressInfo) : '',
            'birthdayCode'  => date('Y-m-d', strtotime($code['birthdayCode'])),
            'constellation' => $this->_getConstellation($code['birthdayCode']),
            'chineseZodiac' => $this->_getChineseZodiac($code['birthdayCode']),
            'sex'           => ($code['order'] % 2 === 0 ? 0 : 1),
            'length'        => $code['type'],
            'checkBit'      => $code['checkBit'],
        ];
    }

    /**
     * * 生成假数据.
     *
     * @param bool            $isEighteen 是否为 18 位
     * @param null|string     $address    地址
     * @param null|string|int $birthday   出生日期
     * @param null|int        $sex        性别（1为男性，0位女性）
     *
     * @return string
     */
    public function fakeId($isEighteen = true, $address = null, $birthday = null, $sex = null)
    {
        // 生成地址码
        $addressCode = $this->_generatorAddressCode($address);

        // 出生日期码
        $birthdayCode = $this->_generatorBirthdayCode($birthday);

        // 顺序码
        $orderCode = $this->_generatorOrderCode($sex);

        if (!$isEighteen) {
            return $addressCode.substr($birthdayCode, 2).$orderCode;
        }

        $body = $addressCode.$birthdayCode.$orderCode;

        $checkBit = $this->_generatorCheckBit($body);

        return $body.$checkBit;
    }

    /**
     * 15位升级18位号码.
     *
     * @param string $id 身份证号
     *
     * @return bool|string
     */
    public function upgradeId($id)
    {
        if (!$this->isValid($id)) {
            return false;
        }
        $code = $this->_generateShortType($id);

        return $code['addressCode'].$code['birthdayCode'].$code['order'].$this->_generatorCheckBit($code['body']);
    }
}
