<?php

class Ex203_RouteClassShortcuts {
    function __construct(){}
    function get() {
        $body = '<html><body><h1>Ex203_RouteClassShortcuts::get</h1></body></html>';
        return [200, 'OK', $headers = [], $body];
    }
    function post() {
        $body = '<html><body><h1>Ex203_RouteClassShortcuts::post</h1></body></html>';
        return [200, 'OK', $headers = [], $body];
    }
}

class Ex203_RouteClassShortcutsWithMap {
    function __construct(){}
    function get(){}
    function zanzibar() {
        $body = '<html><body><h1>Ex203_RouteClassShortcutsWithMap::zanzibar</h1></body></html>';
        return [200, 'OK', $headers = [], $body];
    }
}