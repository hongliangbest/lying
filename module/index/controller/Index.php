<?php
namespace module\index\controller;

use lying\service\Controller;

class Index extends Controller
{
    public $layout = 'layout';
    
    public function index()
    {
        return $this->render('index');
    }

    public function user($id)
    {



    }
}
