<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: McDonald's <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace org;
class Verify
{
    protected $config = [
        'seKey' => 'ThinkPHP.CN', // verification code encryption key
        'codeSet' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY', // verification code character set
        'expire' => 1800, // verification code expiration time (s)
        'useZh' => false, // use Chinese verification code
        'zhSet'    =>'We think that when I get to the school, I have to move the domestic one, the first is the work, the age is the righteousness, the people can come out, the people can come out, and there is a difference between the masters and the middlemen. It is useful for learning the lower grades. Colleagues said that the plant was born and revolutionized and had many children. After the company added a small machine, it also passed through the power line. Waiting for the anti-body combination road map to change the new theory of Jietiri from the current two talents, the teams approval, the cultivation of their thoughts and the time to go to the internal affairs due to the daily interests and the pressure of the energy industry to replace the whole group with the results. Qi Dao Ping, each base or Yue Mao Ran, if you want to control your mind and do everything you need to do, ask for a comparison show, then it’s the most important thing. However, it is more difficult and the leader of Confucius flows into the seat controller and the crude oil is released.Nine, you took the west holding the general material for re-election, Zhiguan, Qi, Shancheng, and hundreds of newspapers. It will be true that the heat protection committee will change the management department. You will repair the support and know the disease. After finishing the wind, I returned to Nanguang, the labor department, and the north, and the car was accumulated in the north. Passing the mouth and breaking the state The county soldiers are solid and remove the teeth. Thousands of winsThe enemy's first film, Shi Xiang, received Hua Jue's name, Hong continued the medicine, the mark is difficult to store, the body is tight, the liquid is sent to the quasi-jin angle, the dimension-reducing board The fire section of the Yamo ethnic group is suitable for speaking according to the value of the beautiful state. Huang Yibiao serves the morning shift. Badly transplanted from Yoba Province, Black Wupei, Hedi, how to plant only needles in Beijing Fuyu was engraved with the examination and relied on being full and lost to contain the mycobacterial rod. Zhou Huyan gave Qu Chunyuan, a super-negative sand seal, and replaced the Taimou poverty. Only in the slip station, another Wei, the word drum just wrote Liu Weilue, Fan for Abu, a certain skill set friend, limited items, Yu Rewind, and the law rain, let the Guyuan Gang, the first skin broadcast, you occupy the death drug circle, Wei Ji, training, control, and call Yun Hu Practice with cracked grain mothers, plug steel top strategy, double retention, basic suction resistance, inch shield late silk female loose welding skills Chengchong sprays soil Jian Fuzhu Li Wangpan Cixiong seems to be trapped Gong Yizhou off the delivery slave sideRun Gai, Swing Distance, Touch Stars, Pine, Get Xing Du Guan, Mixed Ji, Yi Wei, Struggling, Wide Winter Chapter The spear is thick and the mud tells the egg box to hold oxygen and love to stop Zeng Rongying. Weak feet are afraid of salt powder, Yin Feng, Guan Bing Street, Lai Bei, Fu Ji, Tiao Rui, Jing Dun, squeeze seconds, Lan Sen, sugar, holy concave, pottery words, late silkworm, Yi Ju The Indian bee urgently takes the expansion injury, Feilu, nuclear edge, You Zhencao, Yang Wuyu, Xunhui, and abnormal order.Yi? Confucianism kills steam, phosphorus, hard crystal, inserts Egypt, burns iron, fills our buds Han Yu, green dragging cattle, dyeing, forging jade, summer therapy, tip planting, Feizhou, visit, blowing, copper edge Fu Zong, Chosen Li, May Fu, Lei Yan, Smoke Sentences Wearing goods, selling, painting, feeding, dragon library, building house, Ge Hanxi, washing and corroding waste, belly, recording, mirror, woman, evil, Zhuang, rubbing insurance, praise, bell, shaking, debate, bamboo valley, selling chaos, virtual bridge, Ober, rushing to the forehead network Jie Yes legacy, a quiet conspiracy, a hang-up town, a prosperous, resistant to aid, a concern, a key, a Fuqing gathering Who is attacked on the island? Hong Xie cannon pours spot news, understands the soul egg, closes the child, releases the milk, the giant private bank, Yi Jingtan, tired, mildew, and DuleleAfternoon jumping in Shang Ding, Qin Shao, chasing Liang, depleting alkali, Shugang, digging swords, drama, Hehe, chest, Hengqin, and film, published in the case journal, Yangzongjiao The flag filter silicon carbon stock is sitting on the steam and actually trapped in the gun Li rescued the dark hole and committed the barrel Pad Dandu ear planer tiger pen sparse Kunlangsa tea drop shallow embrace point Fu Lunniang t dipped sleeve bead female mother purple play tower hammer shocking age appearance clean cut prison frontSuspected bully Shampu sued slap ruthlessly and suddenly caused disaster and trouble Qiao Tang leaked smell of melting chlorine barren stem male Fan grabbed image pulp next to glass Yi Zhong sang Meng Yu and arrested Suu You Cheng Wu Zhi lightly allowed traitorous animal prisoners to touch rust and sweep Master Bi Li Bao Xin Jian Jianjing Jing Jiang Cai shoulders withered, throws the track, mixes the father, follows the lure, Zhu Liken, the wine rope, the poor pond, the dry foam bag, the long feeding the aluminum soft canal, the customary trade manure, the comprehensive wall off loan', // Chinese verification code string
        'useImgBg' => false, // use background image
        'fontSize' => 25, // Verification code font size (px)
        'useCurve' => true, // whether to draw a confusion curve
        'useNoise' => true, // whether to add noise
        'imageH' => 0, // Captcha image height
        'imageW' => 0, // captcha image width
        'length' => 5, // verification code digits
        'fontttf' => '', // Verification code font, do not set random access
        'bg' => [243, 251, 254], // background color
        'reset' => true, // whether to reset after successful verification
    ];
    private $_image = null; // verification code image instance
    private $_color = null; // Verification code font color
    /**
     * Architecture method set parameters
     * @access public
     * @param array $config configuration parameters
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }
    /**
     * Use $this->name to get configuration
     * @access public
     * @param string $name Configuration name
     * @return multitype configuration value
     */
    public function __get($name)
    {
        return $this->config[$name];
    }
    /**
     * Set verification code configuration
     * @access public
     * @param string $name Configuration name
     * @param string $value configuration value
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }
    /**
     * Check configuration
     * @access public
     * @param string $name Configuration name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }
    /**
     * Verify that the verification code is correct
     * @access public
     * @param string $code User verification code
     * @param string $id verification code ID
     * @return bool whether the user verification code is correct
     */
    public function check($code, $id = '')
    {
        $key = $this->authcode($this->seKey) . $id;
        // verification code must be filled
        $secode = session($key);
        if (empty($code) || empty($secode)) {
            return false;
        }
        // session expires
        if (time() - $secode['verify_time'] > $this->expire) {
            session($key, null);
            return false;
        }
        if ($this->authcode(strtoupper($code)) == $secode['verify_code']) {
            $this->reset && session($key, null);
            return true;
        }
        return false;
    }
    /**
     * Output the verification code and save the value of the verification code in the session
     * The format of the verification code saved to the session is: array('verify_code' => 'verification code value', 'verify_time' => 'verification code creation time');
     * @access public
     * @param string $id ID to generate verification code
     * @return void
     */
    public function entry($id = '')
    {
        // Image width (px)
        $this->imageW || $this->imageW = $this->length * $this->fontSize * 1.5 + $this->length * $this->fontSize / 2;
        // Image height (px)
        $this->imageH || $this->imageH = $this->fontSize * 2.5;
        // Build an image of $this->imageW x $this->imageH
        $this->_image = imagecreate($this->imageW, $this->imageH);
        // set background
        imagecolorallocate($this->_image, $this->bg[0], $this->bg[1], $this->bg[2]);
        // random color of captcha font
        $this->_color = imagecolorallocate($this->_image, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));
        // The verification code uses a random font
        $ttfPath = dirname(__FILE__) . '/verify/' . ($this->useZh ? 'zhttfs' : 'ttfs') . '/';
        if (empty($this->fontttf)) {
            $dir  = dir($ttfPath);
            $ttfs = [];
            while (false !== ($file = $dir->read())) {
                if ('.' != $file[0] && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $dir->close();
            $this->fontttf = $ttfs[array_rand($ttfs)];
        }
        $this->fontttf = $ttfPath . $this->fontttf;
        if ($this->useImgBg) {
            $this->_background();
        }
        if ($this->useNoise) {
            // draw noise
            $this->_writeNoise();
        }
        if ($this->useCurve) {
            // draw the interference line
            $this->_writeCurve();
        }
        // draw verification code
        $code = []; // verification code
        $codeNX = 0; // the left margin of the Nth character of the verification code
        if ($this->useZh) {
            // Chinese verification code
            for ($i = 0; $i < $this->length; $i++) {
                $code[$i] = iconv_substr($this->zhSet, floor(mt_rand(0, mb_strlen($this->zhSet, 'utf-8') - 1)), 1, 'utf-8');
                imagettftext($this->_image, $this->fontSize, mt_rand(-40, 40), $this->fontSize * ($i + 1) * 1.5, $this->fontSize + mt_rand(10, 20), $this->_color, $this->fontttf, $code[$i]);
            }
        } else {
            for ($i = 0; $i < $this->length; $i++) {
                $code[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
                $codeNX += mt_rand($this->fontSize * 1.2, $this->fontSize * 1.6);
                imagettftext($this->_image, $this->fontSize, mt_rand(-40, 40), $codeNX, $this->fontSize * 1.6, $this->_color, $this->fontttf, $code[$i ]);
            }
        }
        // save the verification code
        $key = $this->authcode($this->seKey);
        $code = $this->authcode(strtoupper(implode('', $code)));
        $secode = [];
        $secode['verify_code'] = $code; // save the verification code to the session
        $secode['verify_time'] = time(); // Verification code creation time
        session($key . $id, $secode);
        header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header("content-type: image/png");
        // output image
        imagepng($this->_image);
        imagedestroy($this->_image);
    }
    /**
     * Draw a random sine function curve composed of two connected together as an interference line (you can change it to a more handsome curve function)
     *
     * I forgot all the math formulas in high school, write them out
     * Analytical formula of sine function: y=Asin(ωx+φ)+b
     * The effect of each constant value on the function image:
     *A: Determines the peak value (ie, the multiple of longitudinal stretching and compression)
     * b: Indicates the positional relationship of the waveform on the Y axis or the vertical movement distance (up and down)
     * φ: Determine the relationship between the waveform and the X-axis position or the lateral movement distance (left plus right minus)
     * ω: decision period (minimum positive period T=2π/∣ω∣)
     *
     */
    private function _writeCurve()
    {
        $px = $py = 0;
        // front part of the curve
        $A = mt_rand(1, $this->imageH / 2); // amplitude
        $b = mt_rand(-$this->imageH / 4, $this->imageH / 4); // Y-axis offset
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X-axis offset
        $T = mt_rand($this->imageH, $this->imageW * 2); // cycle
        $w = (2 * M_PI) / $T;
        $px1 = 0; // start position of curve abscissa
        $px2 = mt_rand($this->imageW / 2, $this->imageW * 0.8); // end position of the abscissa of the curve
        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i = (int) ($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_image, $px + $i, $py + $i, $this->_color); // Here (while) loop to draw pixels than imagettftext and imagestring with font size at one time (do not use this while loop) performance is much better
                    $i--;
                }
            }
        }
        // the back part of the curve
        $A = mt_rand(1, $this->imageH / 2); // amplitude
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X-axis offset
        $T = mt_rand($this->imageH, $this->imageW * 2); // cycle
        $w = (2 * M_PI) / $T;
        $b   = $py - $A * sin($w * $px + $f) - $this->imageH / 2;
        $px1 = $px2;
        $px2 = $this->imageW;
        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i  = (int) ($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_image, $px + $i, $py + $i, $this->_color);
                    $i--;
                }
            }
        }
    }
    /**
    * Paint noise
     * Write letters or numbers in different colors on the picture
     */
    private function _writeNoise()
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //noise color
            $noiseColor = imagecolorallocate($this->_image, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // draw noise
                imagestring($this->_image, 5, mt_rand(-10, $this->imageW), mt_rand(-10, $this->imageH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }
    /**
     * Draw background image
     * Note: If the verification code output image is relatively large, it will take up more system resources
     */
    private function _background()
    {
        $path = dirname(__FILE__) . '/verify/bgs/';
        $dir  = dir($path);
        $bgs = [];
        while (false !== ($file = $dir->read())) {
            if ('.' != $file[0] && substr($file, -4) == '.jpg') {
                $bgs[] = $path . $file;
            }
        }
        $dir->close();
        $gb = $bgs[array_rand($bgs)];
        list($width, $height) = @getimagesize($gb);
        // Resample
        $bgImage = @imagecreatefromjpeg($gb);
        @imagecopyresampled($this->_image, $bgImage, 0, 0, 0, 0, $this->imageW, $this->imageH, $width, $height);
        @imagedestroy($bgImage);
    }
    /* Encrypted verification code */
    private function authcode($str)
    {
        $key = substr(md5($this->seKey), 5, 8);
        $str = substr(md5($str), 8, 10);
        return md5($key . $str);
    }
}