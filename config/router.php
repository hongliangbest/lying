<?php
//可选参数都应该被注释或设置成等于false的值
//除了suffix,其他配置都对大小写敏感
return [
    //默认域名配置;必须
    'default'=>[
        //绑定的module;可选,默认为index,如果定义了此参数,就没有办法通过path访问其他模块了
        'module'=>'index',
        //默认控制器;可选,默认为index
        'ctrl'=>'index',
        //默认方法;可选,默认为index
        'action'=>'index',
        //默认后缀;可选
        'suffix'=>'.html',
        //路由规则;必须,可以为空数组
        'rule'=>[
            'u/:time/:id'=>['index/user-name', 'time'=>'/^\d{4}-\d{1,2}-\d{1,2}$/'],
        ],
    ],
    //指定域名配置;可选
    'admin.lying.com'=>[
        //绑定的module;可选,默认为index,如果定义了此参数,就没有办法通过path访问其他模块了
        'module'=>'admin',
        //默认控制器;可选,默认为index
        'ctrl'=>'index',
        //默认方法;可选,默认为index
        'action'=>'index',
        //默认后缀;可选
        'suffix'=>'.html',
        //路由规则;必须,可以为空数组
        'rule'=>[
            
        ],
    ],
];