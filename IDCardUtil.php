<?php

/**
 * 身份证验证的工具（支持15位或18位省份证）
 * 身份证号码结构：
 * <p>
 * 根据〖中华人民共和国国家标准GB11643-1999〗中有关公民身份号码的规定，公民身份号码是特征组合码，由十七位数字本体码和一位数字校验码组成。
 * 排列顺序从左至右依次为：6位数字地址码，8位数字出生日期码，3位数字顺序码和1位数字校验码。
 * <p>
 * 地址码（前6位）：表示对象常住户口所在县（市、镇、区）的行政区划代码，按GB/T2260的规定执行。
 * <li>前1、2位数字表示：所在省份的代码；</li>
 * <li>第3、4位数字表示：所在城市的代码；</li>
 * <li>第5、6位数字表示：所在区县的代码；</li>
 * <p>
 * 出生日期码，（第7位 - 14位）：表示编码对象出生年、月、日，按GB按GB/T7408的规定执行，年、月、日代码之间不用分隔符。
 * <p>
 * 顺序码（第15位至17位）：表示在同一地址码所标示的区域范围内，对同年、同月、同日出生的人编订的顺序号，顺序码的奇数分配给男性，偶数分配给女性。
 * <li>第15、16位数字表示：所在地的派出所的代码；</li>
 * <li>第17位数字表示性别：奇数表示男性，偶数表示女性；</li>
 * <li>第18位数字是校检码：也有的说是个人信息码，一般是随计算机的随机产生，用来检验身份证的正确性。校检码可以是0~9的数字，有时也用x表示。</li>
 * <p>
 * 校验码（第18位数）：
 * <p>
 * 十七位数字本体码加权求和公式 s = sum(Ai*Wi), i = 0..16，先对前17位数字的权求和；
 * Ai:表示第i位置上的身份证号码数字值.Wi:表示第i位置上的加权因子.Wi: 7 9 10 5 8 4 2 1 6 3 7 9 10 5 8 4 2；
 * 计算模 Y = mod(S, 11)
 * 通过模得到对应的模 Y: 0 1 2 3 4 5 6 7 8 9 10 校验码: 1 0 X 9 8 7 6 5 4 3 2
 * <p>
 * 计算步骤：
 * 1.将前17位数分别乘以不同的系数。从第1位到第17位的系数分别为：7 9 10 5 8 4 2 1 6 3 7 9 10 5 8 4 2
 * 2.将这17位数字和系数相乘的结果相加。
 * 3.用加出来和除以11，看余数是多少
 * 4.余数只可能有0 1 2 3 4 5 6 7 8 9 10这11个数字，分别对应的最后一位身份证的号码为：1 0 X 9 8 7 6 5 4 3
 * <p>
 */
class IDCardUtil
{
	//省份证号的长度
	private $id_len;
	/**
	 * <pre>
	 * 省、直辖市代码表：
	 *     11 : 北京  12 : 天津  13 : 河北   14 : 山西  15 : 内蒙古
	 *     21 : 辽宁  22 : 吉林  23 : 黑龙江 31 : 上海  32 : 江苏
	 *     33 : 浙江  34 : 安徽  35 : 福建   36 : 江西  37 : 山东
	 *     41 : 河南  42 : 湖北  43 : 湖南   44 : 广东  45 : 广西  46 : 海南
	 *     50 : 重庆  51 : 四川  52 : 贵州   53 : 云南  54 : 西藏
	 *     61 : 陕西  62 : 甘肃  63 : 青海   64 : 宁夏  65 : 新疆
	 *     71 : 台湾
	 *     81 : 香港  82 : 澳门
	 *     91 : 国外
	 * </pre>
	 */
	private static $CITY_CODE = [
		"11",
		"12",
		"13",
		"14",
		"15",
		"21",
		"22",
		"23",
		"31",
		"32",
		"33",
		"34",
		"35",
		"36",
		"37",
		"41",
		"42",
		"43",
		"44",
		"45",
		"46",
		"50",
		"51",
		"52",
		"53",
		"54",
		"61",
		"62",
		"63",
		"64",
		"65",
		"71",
		"81",
		"82",
		"91"
	];

	/**
	 * 校验码
	 */
	private static $PARITYBIT = [
		'1',
		'0',
		'X',
		'9',
		'8',
		'7',
		'6',
		'5',
		'4',
		'3',
		'2'
	];

	/**
	 * 加权因子
	 * Math.pow(2,  i - 1) % 11
	 */
	private static $POWER = [
		7,
		9,
		10,
		5,
		8,
		4,
		2,
		1,
		6,
		3,
		7,
		9,
		10,
		5,
		8,
		4,
		2
	];

	public function __construct($id)
	{
		$this->id_len = strlen($id);
		/////
		var_dump($this->isValid($id));
	}

	/**
	 * 身份证验证
	 *
	 * @param $id string 号码内容
	 * @return  bool 是否有效
	 */
	public function isValid($id)
	{
		if (empty($id))
		{
			return false;
		}
		if (!in_array($this->id_len, [
			15,
			18
		]))
		{
			return false;
		}

		//校验区位码
		if (!$this->validCityCode(substr($id, 0, 2)))
		{
			return false;
		}
		//校验生日
		
		if (!$this->validDate($id))
		{
			return false;
		}
		//校验位数,即最后一位是否匹配
		if ($this->validParityBit($id) == substr($id,-1))
		{
			return  $id.'有效..';
		}
		return false;
	}

	//效验区位码
	private function validCityCode($id)
	{
		if (in_array($id, self::$CITY_CODE))
		{
			return true;
		}
		return false;
	}

	//校验生日
	private function validDate($id)
	{
		$birth = strlen($id) == 15 ? '19' . substr($id, 6, 12) : substr($id, 6, 14);
		//缺少生日日期  合法验证逻辑///
		if ($birth)
		{
			return true;
		}
	}

	//校验位数
	private function validParityBit($id)
	{
		$id_array = str_split(strtoupper($id));
		//return $id_array;
		$power = 0;
		for ($i = 0; $i < strlen($id); $i++)
		{
			//最后一位可以是X
			if ($i == strlen($id) - 1 && $id_array[$i] == 'X')
			{
				break;
			}
			//非数字
			if ($id_array[$i] < 0 || $id_array[$i] > 9)
			{
				return false;
			}
			//核心 加权求和
			if ($i < strlen($id) - 1)
			{
				$power += ($id_array[$i] - 0) * self::$POWER[$i];
			}
		}
		return self::$PARITYBIT[ $power % 11];
	}
}

///效验
new IDCardUtil('140223199407144215');