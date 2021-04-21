<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\FormControllerException;

class FormController extends Controller
{

    protected $handled = [];
    
    public function entrance(Request $request)
    {

        if (empty($request->input('text'))) return view('form');
        else {

            //

        }

    }

    protected function handle(string $text)
    {

        if (!empty($handled)) $text = trim($text, " .,!?\n\r\t\0\v");

        $open = substr($text, 0, 1);

        try {

            $close = $this->closeBracket($open);

        } catch (FormControllerException $e) {}

        if (isset($e) ||
            substr($text, -1, 1) !==
                $close) throw new FormControllerException(
            'Строка "'.$text.'" не является корректной.',
            -1
        );

        $text = trim($text, $open.$close);

        $next_level = [];

        foreach (['(', '[', '{'] as $b) {

            $pos = strpos($text, $b);

            if ($pos !== false) {

                $write = true;

                if (isset($next_level['open'])) {

                    if ($next_level['open']['pos'] < $pos) $write = false;

                }

                if ($write === true) $next_level['open'] = [
                    'bracket' => $b,
                    'pos' => $pos
                ];

            }

        }

        foreach ([')', ']', '}'] as $b) {

            $pos = strrpos($text, $b);

            if ($pos !== false) {

                $write = true;

                if (isset($next_level['close'])) {

                    if ($next_level['close']['pos'] > $pos) $write = false;

                }

                if ($write === true) $next_level['close'] = [
                    'bracket' => $b,
                    'pos' => $pos
                ];

            }

        }

        if (!isset($next_level['open']) &&
            isset($next_level['close'])) throw new FormControllerException(
                'В строке "'.$text.'"обнаружен некорректный уровень вложенности.',
                -3
            );

        //

    }

    protected function closeBracket(string $open_bracket) : string
    {

        switch ($open_bracket) {

            case '(':
                $close_bracket = ')';
                break;

            case '{':
                $close_bracket = '}';
                break;

            case '[':
                $close_bracket = ']';
                break;

            default:
                $close_bracket = '';
                break;

        }

        if (empty($close_bracket)) throw new FormControllerException(
            'Некорректная открывающая скобка.',
            -2
        );

        return $close_bracket;

    }

}
