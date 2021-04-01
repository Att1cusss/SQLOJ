<?php
/*************************************************************************************************
 * **********************************************************************************************
 * 
 *          文件类(classFile) 提供了SQLOJ系统中常用の涉及文件操作の方法
 *          它的实例化表示<一个文件>
 *          2021/3/2    版本1.0     构建文件类并写了一些基本函数用于测试
 *          2021/3/5    版本1.1     完善代码、增强可读性
 *          2021/3/7    版本2.0     重写文件类、增加读写json函数
 *          2021/3/12   版本2.1     增加url重定向函数
 *          
 * +--+-+-+--+-+-+-+--+函数功能说明+-+-+-+-+-+-+-+-+-+-+-+-+--+-+-+-+-+-+-
 * 
 * 1.__construct($url)                      |   构造函数
 * 2.__destruct()                           |   析构函数
 * 3.WriteTxt($str)                         |   向txt写入string返回bool
 * 4.ReadTxt()                              |   从txt读出string并返回
 * 5.WriteJson($arr)                        |   接收数组转为json对象后写入json文件
 * 6.ReadJson()                             |   读json文件返回json对象
 * 7.Redirect($newUrl)                      |   url重定向
 * 
 * **********************************************************************************************
 ***********************************************************************************************/
class File{
    protected $url = NULL;

    public function __construct($url) {
        $this->url = $url;
    }
    public function __destruct() {
        unset($this->url);
    }
    //向txt写入string返回bool
    public function WriteTxt($str) {
        return (bool)@file_put_contents($this->url, $str, LOCK_EX);//写入时独占锁
    }
    //从txt读出string并返回
    public function ReadTxt() {
        return @file_get_contents($this->url);
    }
    //接收数组转为json对象后写入json文件
    public function WriteJson($arr) {
        if (!isset($arr) || empty($arr)) {
            return false;
        }
        return (bool)@file_put_contents($this->url, json_encode($arr), LOCK_EX);
    }
    //读json文件返回json对象
    public function ReadJson() {
        return @json_decode(@file_get_contents($this->url));
    }
    //重定向url
    public function Redirect($newUrl) {
        $this->url = $newUrl;
    }
}