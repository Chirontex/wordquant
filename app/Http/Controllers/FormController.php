<?php

namespace App\Http\Controllers;

use App\Models\Word;
use App\Exceptions\FormControllerException;
use Illuminate\Http\Request;

class FormController extends Controller
{

    protected $level = 0;

    protected $handled = [];
    
    public function entrance(Request $request)
    {

        if (empty($request->input('text'))) return view('form');
        else {

            try {

                $this->handle($request->input('text'));

            } catch (FormControllerException $e) {

                return view(
                    'form',
                    [
                        'notice' => [
                            'type' => 'danger',
                            'text' => $e->getMessage()
                        ]
                    ]
                );

            }

            foreach ($this->handled as $level => $words) {

                $level = explode('_', $level);
                $level = $level[1];

                foreach ($words as $word) {

                    $model = Word::where('level', $level)
                        ->where('word', $word)->get();

                    if (empty($model)) {

                        $model = new Word;

                        $model->word = $word;
                        $model->level = $level;
                        $model->count = 0;

                    }

                    $model->count += 1;

                    $model->save();

                }

            }

            return view(
                'form',
                [
                    'notice' => [
                        'type' => 'success',
                        'text' => 'Строка успешно сохранена!'
                    ]
                ]
            );

        }

    }

    protected function handle(string $text) : self
    {

        $this->level += 1;

        if (!empty($handled)) $text = trim($text, " .,!?\n\r\t\0\v");

        $open = substr($text, 0, 1);

        $close = $this->getCloseBracket($open);

        if (empty($close) ||
            substr($text, -1, 1) !==
                $close) throw new FormControllerException(
            'Строка "'.$text.'" не является корректной.',
            -1
        );

        $text = trim($text, $open.$close);

        /**
         * Надо переписать с этого места.
         */
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

        if ((!isset($next_level['open']) &&
                isset($next_level['close'])) ||
            isset($next_level['open']) &&
            !isset($next_level['close'])) throw new FormControllerException(
                'В строке "'.$text.
                    '" обнаружен некорректный уровень вложенности.',
                -3
            );

        if (!empty($next_level)) {
            
            $next_level_text = substr(
                $text,
                $next_level['open']['pos'],
                iconv_strlen($text) - $next_level['close']['pos']
            );

            $text_start = substr($text, 0, $next_level['open']['pos']);

            $text_end = substr($text, $next_level['close']['pos']);

            $text = trim($text_start).' '.trim($text_end);
        
        }

        $text = str_replace(
            ['.', ',', '!', '?'],
            ' ',
            $text
        );

        $text = explode(' ', $text);

        if (count($text) < 3) throw new FormControllerException(
            'Уровень должен содержать как минимум 3 слова.',
            -4
        );

        $text = array_map(function($value) {

            return trim($value);

        }, $text);

        $this->handled['level_'.$this->level] = $text;

        if (isset($next_level_text)) $this->handle($next_level_text);

        return $this;

    }

    protected function extractNextLevels(string $text) : array
    {

        $result = [];

        $fn = function(string $text, array $brackets) {

            $br = '';
            $pos = 0;

            foreach ($brackets as $b) {

                $b_pos = strpos($text, $b);

                if ($b_pos !== false) {

                    if ($pos === 0 ||
                        $pos > $b_pos) {
                            
                        $pos = $b_pos;

                        $br = $b;
                    
                    }

                }

            }

            if ($br === '') return [];
            
            return ['bracket' => $br, 'pos' => $pos];

        };

        while (str_contains($text, '{') ||
                str_contains($text, '[') ||
                str_contains($text, '(') ||
                str_contains($text, '}') ||
                str_contains($text, ']') ||
                str_contains($text, ')')) {

            $sub = [
                'open' => call_user_func($fn, $text, ['{', '[', '(']),
                'close' => call_user_func($fn, $text, ['}', ']', ')'])
            ];

            if (!empty($sub['open']) &&
                !empty($sub['close']) &&
                $sub['close']['bracket'] ===
                    $this->getCloseBracket($sub['open']['bracket'])) {

                $sub['text'] = substr(
                    $text, 
                    $sub['open']['pos'],
                    $sub['close']['pos'] + 1);

                $text_start = substr($text, 0, $sub['open']['pos']);

                $text_end = substr($text, $sub['close']['pos']);

                $text = $text_start.$text_end;

                if (!empty($result)) {
                    
                    $sub['open']['pos'] +=
                        $result[count($result) - 1]['open']['pos'];
                    
                    $sub['close']['pos'] +=
                        $result[count($result) - 1]['close']['pos'];
                
                }

                $result[] = $sub;

            }

        }

        return $result;

    }

    protected function getCloseBracket(string $open) : string
    {

        switch ($open) {

            case '(':
                $close = ')';
                break;

            case '{':
                $close = '}';
                break;

            case '[':
                $close = ']';
                break;

            default:
                $close = '';
                break;

        }

        if (empty($close)) throw new FormControllerException(
            'Некорректная скобка '.$open.'.',
            -2
        );

        return $close;

    }

}
