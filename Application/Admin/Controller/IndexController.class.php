<?php
namespace Admin\Controller;
use Think\Controller;

use Org\Wechat\Wechat;

class IndexController extends Controller {
    public function index(){
    	$wx=new Wechat();
    	$w->valid();
    }
}