<?php

namespace Impulse\Pulsifier\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\CodeCoverage\Report\PHP;

require 'PulseDefinitions.php';

class Seek
{
    public static function where($field){
        return [
            'field' => $field,
            'template' => PS_WHERE_EQUAL
        ];
    }
    public static function orWhere($field){
        return [
            'field' => $field,
            'template' => PS_OR_WHERE_EQUAL
        ];
    }
    public static function whereLike($field){
        return [
            'field' => $field,
            'template' => PS_WHERE_LIKE
        ];
    }
    public static function orWhereLike($field){
        return [
            'field' => $field,
            'template' => PS_OR_WHERE_LIKE
        ];
    }
    public static function whereBetween($field){
        return [
            'field' => $field,
            'template' => PS_WHERE_BETWEEN
        ];
    }
    public static function orWhereBetween($field){
        return [
            'field' => $field,
            'template' => PS_OR_WHERE_BETWEEN
        ];
    }
    public static function whereRaw($expression){
        return [
            'field' => $expression,
            'template' => PS_WHERE_RAW
        ];
    }
    public static function orWhereRaw($expression){
        return [
            'field' => $expression,
            'template' => PS_OR_WHERE_RAW
        ];
    }

    /**
     * @param $relation
     * @param array of Seek static instance
     * @return array
     */
    public static function whereHas($relation, array $seekers)
    {
        return [
            'field' => $relation,
            'template' => PS_WHERE_HAS,
            'seekers' => $seekers
        ];
    }

    /**
     * @param $relation
     * @param array of Seek static instance
     * @return array
     */
    public static function orWhereHas($relation, array $seekers)
    {
        return [
            'field' => $relation,
            'template' => PS_OR_WHERE_HAS,
            'seekers' => $seekers
        ];
    }

    public static function getMethodBlock($file_name, $method, $access_modifier = "public"){

        $content = file_get_contents(app_path(Config::get('pulsifier.model_path').$file_name));
        $occurrence = strstr($content,"${access_modifier} function ${method}()");
        return self::getBracketBlock($occurrence);
    }

    public static function getRouteBlock($name){
        $content = file_get_contents(app_path("..\\routes\\api.php"));
        $occurrence = strstr($content,"Route::prefix('${name}')->name('${name}')->group(function(){");
        return $occurrence;
    }

    public static function getBracketBlock($occurrence){
        $block = "";
        $open_bracket_count = 0;
        $close_bracket_count = 0;
        foreach (preg_split('/\r\n|\r|\n/', $occurrence) as $line) {
            $block .= $line.PHP_EOL;
            preg_match_all('/[{]|^[(){]/', $line, $open);
            preg_match_all('/[}]|^[};]/', $line, $close);
            $open_bracket_count += count($open[0]);
            $close_bracket_count += count($close[0]);
            if($open_bracket_count != 0 && $open_bracket_count == $close_bracket_count)
                break;
        }
        return $block;
    }

    public static function getBlockSavableRelationType($block){
        if(strpos($block,'$this->belongsToMany')){
            return PS_BELONGS_TO_MANY_SAVABLE_RELATION;
        }else if(strpos($block,'$this->hasMany')){
            return PS_HAS_MANY_SAVABLE_RELATION;
        }else if(strpos($block,'$this->hasOne')){
            return PS_HAS_ONE_SAVABLE_RELATION;
        }
        return false;
    }

    public static function getBlockSavableRelationClassName($block){
        preg_match('/\((.*?),/',$block,$matches,PREG_OFFSET_CAPTURE,strpos($block,'return'));
        return preg_replace(['/\(/','/,/','/::class/','/App\\\\/','/\\\\App\\\\/','/\'/'],'',$matches[0][0]);
    }

    public static function ReformatWrite($string){
        $tab = 0;
        self::formatCode(preg_split('/\r\n|\r|\n/', $string),$tab);
    }

    private static function formatCode(array $strings, &$tabs){
        foreach ($strings as $index => $line) {
            $line = trim($line);
            self::breakBrackets($line);
            if(is_array($line)){
//                print_r($line);
                self::formatCode($line,$tabs);
            }else{
                if(strcmp($line,"}") === 0) {
                    $tabs -= $tabs == 0 ? 0 : 1;
                }
//                echo $index .'->'. str_repeat("\t",$tabs).$line.PHP_EOL;
//                echo str_repeat("\t",$tabs).$line.PHP_EOL;
                if(strcmp($line,"{") === 0) {
                    $tabs++;
                }
            }
        }
    }

    private static function breakBrackets(&$line){
        if(strlen($line) != 1 && (strpos($line,"{") !== false || strpos($line,"}") !== false)){
            preg_match_all('/[{|}]/',$line,$bracket_count, PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL);
            if(count($bracket_count[0]) !== 0){
                foreach ($bracket_count[0] as $count) {
                    $line = substr_replace($line,'`',$count[1] + substr_count($line,'`'), 0);
                    $line = substr_replace($line,'`',($count[1] + substr_count($line,'`') + 1), 0);
                }
                $line = str_replace('`',PHP_EOL,$line);
            }
            $line = preg_split('/\r\n|\n\r/',$line, -1, PREG_SPLIT_NO_EMPTY);
        }
    }
}
