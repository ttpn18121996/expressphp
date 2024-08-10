<?php

namespace Expressphp\Support;

use ReflectionClass;

class Dumper
{
    public const string ARRAY_TYPE = 'array';
    public const string BOOL_TYPE = 'boolean';
    public const string FLOAT_DOUBLE_TYPE = 'double';
    public const string INTEGER_TYPE = 'integer';
    public const string OBJECT_TYPE = 'object';
    public const string STRING_TYPE = 'string';
    public const string NULL_TYPE = 'NULL';
    public const array BASE_TYPE = [
        self::BOOL_TYPE, self::FLOAT_DOUBLE_TYPE, self::INTEGER_TYPE, self::STRING_TYPE, self::NULL_TYPE
    ];

    private string $content;

    /**
     * Xử lý phân tích dữ liệu đưa vào.
     */
    public function dump(mixed $arg): void
    {
        if (!defined("DUMP_DEBUG_SCRIPT")) {
            define("DUMP_DEBUG_SCRIPT", true);

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1];
            echo $backtrace['file'].':'.$backtrace['line'];
            echo $this->getStyle('dark');
            echo $this->getScript();
        }
        
        $type = gettype($arg);

        switch ($type) {
            case self::STRING_TYPE:
                $this->content = $this->getContentTypeBase($type, strlen($arg), '"'.htmlentities($arg).'"');
                break;
            case self::INTEGER_TYPE:
                $this->content = $this->getContentTypeBase($type, strlen((string) $arg), $arg);
                break;
            case self::FLOAT_DOUBLE_TYPE:
                $this->content = $this->getContentTypeBase($type, strlen((string) $arg), $arg);
                break;
            case self::BOOL_TYPE:
                $this->content = $this->getContentTypeBase($type, strlen($arg), $arg);
                break;
            case self::NULL_TYPE:
                $this->content = $this->getContentTypeBase($type, 0, 'null');
                break;
            case self::ARRAY_TYPE:
                $this->content = $this->getContentTypeArray($arg);
                break;
            case self::OBJECT_TYPE:
                $this->content = $this->getContentTypeObject($arg);
                break;

            default:
                $this->content = $this->getContentTypeBase($type, 0, 'null');
        }

        echo $this->content.'<div class="mt-2 mb-2"><hr/></div>';
    }

    /**
     * Lấy nội dung dữ liệu dạng đối tượng.
     */
    public function getContentTypeObject(object $obj, int $level = 0): string
    {
        $type = get_class($obj);
        $id = spl_object_id($obj);
        if ($type != 'stdClass') {
            $ref = new ReflectionClass($type);
            $temp = [];
            $props = $ref->getProperties();
            foreach ($props as $prop) {
                $prop->setAccessible(true);

                $temp[(($prop->isPublic())
                    ? '[+] '
                    : (($prop->isPrivate()) ? '[-] ' : '[#] ')).$prop->name] = $prop->getValue($obj);
            }
            $obj = $temp;
        } else {
            $obj = (array) $obj;
        }

        return $this->getContentTypeArray($obj, $level, $type, $id);
    }

    /**
     * Lấy nội dung dữ liệu dạng dữ liệu thuần.
     */
    private function getContentTypeBase(
        string $type,
        int $length,
        string $value,
        ?string $key = null,
        string $charPoint = ' => ',
    ): string {
        $content = '<span class="dumper-type">'.$type.'('.$length.') </span>
                <span class="dumper-value-'.$type.'">'.$value.'</span>';

        if ($type == self::NULL_TYPE) {
            $content = '<span class="dumper-type">'.strtolower($type).'</span>';
        } elseif ($type == self::BOOL_TYPE) {
            $content = '<span class="dumper-type">'.$type.'('.$length.') </span>
                <span class="dumper-value-'.$type.'">'.($value ? 'true' : 'false').'</span>';
        }

        if (is_null($key)) {
            return '<div id="id-'.rand().'">'.$content.'</div>';
        }

        return '<span class="dumper-key">'.$key.$charPoint.'</span>'.$content;
    }

    /**
     * Lấy nội dung dữ liệu dạng mảng.
     */
    private function getContentTypeArray(array $data, int $level = 0, string $type = 'array', int $objectId = 0): string
    {
        $length = count($data);
        $charPoint = $type != 'array' ? ': ' : ' => ';
        $blank = '';
        for ($i = 0; $i < $level; $i++) {
            $blank .= '&nbsp;&nbsp;';
        }

        $rs = '';

        if ($level == 0) {
            $rs .= '<div id="id-'.rand().'-'.$level.'">
            '.$blank;
        }

        $rs .= '<span class="dumper-'.($type != 'array' ? 'value-obj' : 'type').' pointer">'.$type.
            ($type == 'array' ? '('.$length.')' : '&nbsp;{...}[#'.$objectId.']').'&nbsp;';
        
        if ($length) {
            if ($level == 9) {
                $rs .= '<span class="dumper-value-obj">{...}</span>';
            } else {
                $rs .= '<span class="dumper-toggle" data-rotate="'.($level == 0 ? '90' : '0').'" style="transform: rotate('.($level == 0 ? '90' : '0').'deg);"></span>';
            }
        }

        $rs .= '</span><div class="dumper-value-arr-obj '.($level == 0 ? '' : 'dnone').'">';
        if ($length) {
            $level++;
            foreach ($data as $key => $value) {
                if ($value instanceof $type) {
                    $rs .= '<div>';
                    for ($i = 0; $i < $level; $i++) {
                        $rs .= '&nbsp;&nbsp;';
                    }

                    $rs .= '<span class="dumper-key">'.$key.': </span>
                    <span class="dumper-value-obj">'.$type.' {...}</span></div>';
                } else {
                    $rs .= $this->getContentItemArray($key, $value, $level, $charPoint);
                }
            }
        }

        $rs .= $level == 0 ? '</div></div>' : '</div>';

        return $rs;
    }

    /**
     * Lấy nội dung dự liệu các phần tử mảng.
     */
    private function getContentItemArray(string $key, mixed $value, int $level, string $charPoint = ' => '): string
    {
        if ($level == 10) {
            return '';
        }

        $type = gettype($value);
        $blank = '';
        for ($i = 0; $i < $level; $i++) {
            $blank .= '&nbsp;&nbsp;';
        }

        $rs = '<div id="id-'.rand().'-'.$level.'">'.$blank;
        if (in_array($type, self::BASE_TYPE)) {
            $value = is_null($value) ? 'null' : $value;
            $value = $type == self::STRING_TYPE ? '"'.htmlentities($value).'"' : htmlentities($value);
            $rs .= $this->getContentTypeBase($type, $type == self::STRING_TYPE ? strlen($value) - 2 : strlen($value), $value, $key, $charPoint);
        } elseif ($type == self::ARRAY_TYPE) {
            $rs .= '<span class="dumper-key">'.$key.$charPoint.'</span>';
            $rs .= $this->getContentTypeArray($value, $level);
        } elseif ($type == self::OBJECT_TYPE) {
            $rs .= '<span class="dumper-key">'.$key.$charPoint.'</span>';
            $rs .= $this->getContentTypeObject($value, $level);
        }

        return $rs.'</div>';
    }

    /**
     * Thiết lập nhúng css.
     */
    private function getStyle(string $theme = 'dark'): string
    {
        if ($theme == 'dark') {
            return '<style>
            *{padding: 0;margin: 0;box-sizing: border-box;font-family: Monaco, Consolas, monospace;font-size: 12px;font-weight: 400;line-height: 1.5rem;color:#fff;}body{background: #0e0e0e;padding: 0.5rem;}
            .dnone{display: none !important;}.dblock{display: block !important;}.pointer{cursor: pointer;}
            .mb-2{margin-bottom: 0.5rem;}.mt-2{margin-top: 0.5rem;}.dumper-type{color: #e87c08;user-select: none;}
            .dumper-value-string{color: #15dd15;}.dumper-value-integer{color: #007bff;}.dumper-value-obj{color: #ffc107;}
            .dumper-value-double{color: #ff11ff;}.dumper-value-boolean{color: #dc3545;}.dumper-key{color: #fefefe;}
            .dumper-toggle{user-select: none;width: 1px;height: 1px;display: inline-block;transition: all 0.25s;
                border-top:5px solid transparent;border-right:5px solid transparent;
                border-bottom:5px solid transparent;border-left:5px solid #fefefe;}
            .dumper-value-arr-obj{transition: all 0.5s ease-in-out;}
            </style>';
        }

        return '<style>
            *{padding: 0;margin: 0;box-sizing: border-box;font-family: Monaco, Consolas, monospace;font-size: 12px;font-weight: bold;line-height: 1.5rem;color:#000;}body{background: #fefefe;padding: 0.5rem;}
            .dnone{display: none !important;}.dblock{display: block !important;}.pointer{cursor: pointer;}
            .mb-2{margin-bottom: 0.5rem;}.mt-2{margin-top: 0.5rem;}.dumper-type{color: #e87c08;user-select: none;}
            .dumper-value-string{color: #28a745;}.dumper-value-integer{color: #007bff;}.dumper-value-obj{color: #e6b218;}
            .dumper-value-double{color: #ff11ff;}.dumper-value-boolean{color: #dc3545;}.dumper-key{color: #0e0e0e;}
            .dumper-toggle{user-select: none;width: 1px;height: 1px;display: inline-block;transition: all 0.25s;
                border-top:5px solid transparent;border-right:5px solid transparent;
                border-bottom:5px solid transparent;border-left:5px solid #0e0e0e;}
            .dumper-value-arr-obj{transition: all 0.5s ease-in-out;}
            </style>';
    }

    /**
     * Thiết lập nhúng css và javascript.
     */
    private function getScript(): string
    {
        // Arial, Helvetica, sans-serif
        return '<script async defer>
            function toggleRotate(child, parent) {
                let rotate;
                const dumperValue = parent.parentNode.querySelector(".dumper-value-arr-obj");
                if (child.dataset.rotate === "0") {
                    rotate = "90";
                    child.dataset.rotate = rotate;
                    dumperValue.classList.remove("dnone");
                } else {
                    rotate = "0";
                    child.dataset.rotate = rotate;
                    dumperValue.classList.add("dnone");
                }
                child.style.transform = "rotate(" + rotate + "deg)";
            }
            document.addEventListener("DOMContentLoaded", function () {
                const pointerElem = document.querySelectorAll(".pointer");
                pointerElem.forEach(function (elem) {
                    elem.addEventListener("click", function () {
                        const child = this.querySelector(".dumper-toggle");
                        if (child) {
                            toggleRotate(child, this);
                        }
                    });
                });
            }, false);
        </script>';
    }
}
