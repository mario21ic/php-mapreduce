<?php

namespace JLSalinas\MapReduce\Tests;

use JLSalinas\MapReduce\MapReduce;
use JLSalinas\MapReduce\ReaderAdapter;
use JLSalinas\RWGen\Readers\Csv;
use JLSalinas\RWGen\Writers\ConsoleJson;

class MapReduceRunUngroupedTest extends \PHPUnit_Framework_TestCase
{
    protected static $data1 = [
        [ 'first_name' => 'susanna', 'last_name' => 'connor',   'gender' => 'f', 'age' => '20'],
        [ 'first_name' => 'adrian',  'last_name' => 'smith',    'gender' => 'm', 'age' => '22'],
        [ 'first_name' => 'mike',    'last_name' => 'mendoza',  'gender' => 'm', 'age' => '24'],
        [ 'first_name' => 'linda',   'last_name' => 'duguin',   'gender' => 'f', 'age' => '26'],
        [ 'first_name' => 'bob',     'last_name' => 'svenson',  'gender' => 'm', 'age' => '28'],
        [ 'first_name' => 'norma',   'last_name' => 'etherden', 'gender' => 'f', 'age' => '30'],
        [ 'first_name' => 'nancy',   'last_name' => 'potier',   'gender' => 'f'],
        [ 'first_name' => 'pete',    'last_name' => 'adams',    'gender' => 'm', 'age' => '32'],
        [ 'first_name' => 'susana',  'last_name' => 'zommers',  'gender' => 'f', 'age' => '34'],
        [ 'first_name' => 'adrian',  'last_name' => 'deville',  'gender' => 'm', 'age' => '36'],
        [ 'first_name' => 'mike',    'last_name' => 'cole',     'gender' => 'm', 'age' => '38'],
        [ 'first_name' => 'mike',    'last_name' => 'angus',    'gender' => 'm', 'age' => '40'],
    ];
    
    protected static $data2 = [
        [ 'first' => 'felix',  'last' => 'connor',  'gender' => 'f', 'birthday' => '1980-05-20'], // 36
        [ 'first' => 'esther', 'last' => 'smith',   'gender' => 'm', 'birthday' => '1986-06-22'], // 30
        [ 'first' => 'mike',   'last' => 'mendoza', 'gender' => 'm', 'birthday' => '1982-07-24'], // 34
        [ 'first' => 'jonas',  'last' => 'hammond', 'gender' => 'm'],
        [ 'first' => 'nancy',  'last' => 'duguin',  'gender' => 'f', 'birthday' => '1984-08-26'], // 32
        [ 'first' => 'bob',    'last' => 'svenson', 'gender' => 'm', 'birthday' => '1978-09-28'], // 38
    ];
    
    protected static function map_eq()
    {
        return function ($item) {
            return isset($item['age']) ? $item : null;
        };
    }
    
    protected static function reduce_age()
    {
        return function ($carry, $item) {
            if (is_null($carry)) {
                return [
                    'count' => '1',
                    'total' => $item['age'],
                    'avg'   => $item['age'],
                    'min'   => $item['age'],
                    'max'   => $item['age'],
                ];
            }
            
            $count = $carry['count'] + 1;
            $total = $carry['total'] + $item['age'];
            $min = min($carry['min'], $item['age']);
            $max = max($carry['max'], $item['age']);
            $avg = $total / $count;
            
            return compact('count', 'total', 'avg', 'min', 'max');
        };
    }
    
    public function testAges()
    {
        $this->expectOutputString('{"count":11,"total":330,"avg":30,"min":"20","max":"40"}' . PHP_EOL);
        $mr1 = (new MapReduce(self::$data1))
                ->map(self::map_eq())
                ->reduce(self::reduce_age())
                ->writeTo(new ConsoleJson())
                ->run();
    }
    
    public function testAgesWithAdapter()
    {
        $this->expectOutputString('{"count":16,"total":500,"avg":31.25,"min":"20","max":"40"}' . PHP_EOL);
        $mr1 = (new MapReduce(self::$data1))
                ->readFrom(new ReaderAdapter(self::$data2, function ($item) {
                    return isset($item['birthday']) ? [
                        'age' => date('Y') - explode('-', $item['birthday'])[0],
                    ] : null;
                }))
                ->map(self::map_eq())
                ->reduce(self::reduce_age())
                ->writeTo(new ConsoleJson())
                ->run();
    }
}
