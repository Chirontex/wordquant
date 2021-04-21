<?php

namespace App\Http\Controllers;

use App\Models\Word;
use App\Exceptions\FormControllerException;
use Illuminate\Http\Request;

class FormController extends Controller
{

    protected $handled = [];
    
    /**
     * Точка входа в контроллер для обработки формы.
     * 
     * @param Request $request
     * Параметры запроса.
     * 
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
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
                        ],
                        'prev_text' => $request->input('text')
                    ]
                );

            }

            foreach ($this->handled as $level => $words) {

                $level = explode('_', $level);
                $level = $level[1];

                foreach ($words as $word) {

                    $word = trim($word);

                    if (!empty($word)) {

                        $model = Word::where('level', $level)
                            ->where('word', $word)
                            ->first();

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

    /**
     * Основной метод обработки строки.
     * 
     * @param string $text
     * Строка из формы.
     * 
     * @param int $level
     * Уровень вложенности.
     * По умолчанию равен 1, т.к. начинаем всегда с первого уровня.
     * 
     * @return $this
     * 
     * @throws App\Exceptions\FormControllerException
     */
    protected function handle(string $text, int $level = 1) : self
    {

        $open = substr($text, 0, 1);

        $close = $this->getCloseBracket($open);

        if (empty($close) ||
            substr($text, -1, 1) !==
                $close) throw new FormControllerException(
            'Строка "'.$text.'" не является корректной.',
            -1
        );

        $text = trim($text, $open.$close);

        $subs = $this->extractNextLevels($text);

        if (!empty($subs)) {

            foreach ($subs as $sub) {

                $text = str_replace($sub['text'], '', $text);

            }

        }

        $text = str_replace(['.', ',', '!', '?'], ' ', $text);
        $text = explode(' ', $text);

        $text = array_map(function($word) {

            return trim($word);

        }, $text);

        if (count($text) < 3) throw new FormControllerException(
            'Уровень должен содержать как минимум 3 слова.',
            -3
        );

        if (isset(
            $this->handled['level_'.$level]
        )) $this->handled['level_'.$level] = array_merge(
            $this->handled['level_'.$level], $text
        );
        else $this->handled['level_'.$level] = $text;

        if (!empty($subs)) {

            foreach ($subs as $sub) {

                $this->handle($sub['text'], $level + 1);

            }

        }

        return $this;

    }

    /**
     * Извлекает следующий уровень вложнности из строки.
     * 
     * @param string $text
     * Строка, из которой производится извлечение.
     * 
     * @return array
     * Ассоциативный массив с участками следующего уровня вложенности
     * и их координатами в исходной строке.
     * 
     * @throws App\Exceptions\FormControllerException
     */
    protected function extractNextLevels(string $text) : array
    {

        $result = [];

        /**
         * Внутренняя функция, помогающая определить координаты и скобку,
         * на которых начинается или заканчивается следующий уровень.
         * 
         * @param string $text
         * Строка, по которой ищем.
         * 
         * @param array $brackets
         * Список доступных скобок. Если ищем конец уровня, то лучше
         * определить доступную скобку по открывающей.
         * 
         * @return array
         * Скобка и координата.
         */
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

            $sub = [];

            $sub['open'] = call_user_func($fn, $text, ['{', '[', '(']);

            if (!empty($sub['open'])) $sub['close'] = call_user_func(
                $fn,
                $text,
                [$this->getCloseBracket($sub['open']['bracket'])]
            );

            if (!empty($sub['open']) &&
                !empty($sub['close'])) {

                $sub['text'] = substr(
                    $text, 
                    $sub['open']['pos'],
                    $sub['close']['pos'] - $sub['open']['pos'] + 1);

                $text = str_replace($sub['text'], '', $text);

                if (!empty($result)) {
                    
                    $sub['open']['pos'] +=
                        $result[count($result) - 1]['open']['pos'];
                    
                    $sub['close']['pos'] +=
                        $result[count($result) - 1]['close']['pos'];
                
                }

                $result[] = $sub;

            } else throw new FormControllerException(
                'Строка "'.$text.'" содержит некорректный уровень вложенности.',
                -4
            );

        }

        return $result;

    }

    /**
     * Возвращает закрывающую скобку по открывающей.
     * 
     * @param string $open
     * Открывающая скобка.
     * 
     * @return string
     * 
     * @throws App\Exceptions\FormControllerException
     */
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
            'Некорректная скобка "'.$open.'".',
            -2
        );

        return $close;

    }

}
