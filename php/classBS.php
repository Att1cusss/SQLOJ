<?php
/************************************************************************
 * 
 *      BitSet类用于记录用户的做题情况
 *      二进制第n位为1(或0)表示用户第n题通过(或未通过)
 *      1个BS对象存储60道题的信息,所以数据库有BSNO表示BS编号,注意对应相应BSNO的BS
 * 
 *************************************************************************/
class BS{
    protected $bs = NULL;

    public function __construct($n = 0) {
        $this->bs = $n;
    }
    public function __destrcut() {
        unset($this->bs);
    }
    //取得第x位的bit 范围1~60 返回0/1/-1
    public function GetBit($x) {
        if ($x > 60) return -1;
        return ($this->bs >> ($x - 1)) & 1;
    }
    //将第x位设为v 范围1~60 返回bool
    public function SetBit($x, $v) {
        if ($x > 60) return false;
        if ($v) {
            $this->bs |= (1 << ($x - 1));
            return true;
        }
        $this->bs &= ~(0 << ($x - 1));
        return true;
    }
    //返回bs
    public function GetBS() {
        return $this->bs;
    }
}