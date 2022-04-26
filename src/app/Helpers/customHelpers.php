<?php
function assets_cache_key ()
{
    return '?id=10477962004341';
}

function russianDate(string $time)
{
    $date=explode(".", date("d.m.Y", strtotime($time)));
    switch ($date[1]){
        case 1: $m='Января'; break;
        case 2: $m='Февраля'; break;
        case 3: $m='Марта'; break;
        case 4: $m='Апреля'; break;
        case 5: $m='Мая'; break;
        case 6: $m='Июня'; break;
        case 7: $m='Июля'; break;
        case 8: $m='Августа'; break;
        case 9: $m='Сентября'; break;
        case 10: $m='Октября'; break;
        case 11: $m='Ноября'; break;
        case 12: $m='Декабря'; break;
    }

    return "$date[0] $m $date[2]";
}

function russianMonth($monthNum) :string
{
	$monthNum = (integer) $monthNum;

	$arr = [
		1 => 'Январь',
		2 => 'Февраль',
		3 => 'Март',
		4 => 'Апрель',
		5 => 'Май',
		6 => 'Июнь',
		7 => 'Июль',
		8 => 'Август',
		9 => 'Сентябрь',
		10 => 'Октябрь',
		11 => 'Ноябрь',
		12 => 'Декабрь'
	];

	return $arr[$monthNum];
}

function dateFormat($dateInString) :string
{
    return date('d.m.Y', strtotime($dateInString)) ?? "-";
}

function dateTimeFormat(string $dateInString) :string
{
	return date('d.m.Y H:i', strtotime($dateInString));
}

function eachStdToArray(array $arrayOfStdCollection)
{
    return array_map(function ($item) {
        return (array) $item;
    }, $arrayOfStdCollection);
}


function mb_ucfirst (string $str, $change_only_first_word = false)
{
    if($change_only_first_word) {
        $arr = explode(' ', $str);

        if(count($arr)) {
            $arr[0] = mb_convert_case($arr[0], MB_CASE_TITLE, 'UTF-8');
            return implode(' ', $arr);
        } else {
            return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
        }

    } else {
        $str = mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
        $arr = explode(' ', $str);

        $firstUpper = mb_convert_case($arr[0], MB_CASE_TITLE, 'UTF-8');
        unset($arr[0]);

        if(count($arr)) {
            return $firstUpper.' '.implode(' ', $arr);
        } else {
            return $firstUpper;
        }
    }
}




function cyrToLat (string $textcyr)
{
    $cyr = [
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
    ];
    $lat = [
        'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
        'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
        'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
    ];

    return str_replace($cyr, $lat, $textcyr);
}


function array_slice_by_key (array $array, string $key, $lenght = null)
{
	if(is_assoc($array)) {
		$index = array_search($key, array_keys($array), true);
	} else {
		$index = array_search($key, $array, true);
	}

    if ($index !== false) {
        $slice = array_slice($array, $index, null, true);
        return $slice;
    }

    return null;
}

function is_assoc(array $array) {
	return (array_values($array) !== $array);
}


function declension (string $word_one, int $int) :string
{
	$last_letter = mb_substr($word_one, -1);
	$last_two_letter = mb_substr($word_one, -2);

	if(in_array($last_two_letter, ['за'])) {
		$word_few = mb_substr($word_one, 0, -1)."ы";
		$word_many = mb_substr($word_one, 0, -1);
	}

	if(in_array($last_two_letter, ['ка'])) {
		$word_few = mb_substr($word_one, 0, -2)."ки";
		$word_many = mb_substr($word_one, 0, -2)."ок";
	}

	if(in_array($last_letter, ['д', 'к', 'р', 'т', 'с'])) {
		$word_few = $word_one."а";
		$word_many = $word_one."ов";
	}

	if(in_array($last_letter, ['я'])) {
		$word_few = mb_substr($word_one, 0, -1)."и";
		$word_many = mb_substr($word_one, 0, -1)."й";
	}

	if(in_array($last_letter, ['ь'])) { //запись
		$word_few = mb_substr($word_one, 0, -1)."и";
		$word_many = mb_substr($word_one, 0, -1)."ей";
	}

	if(in_array($last_letter, ['о'])) {
		$word_few = mb_substr($word_one, 0, -1)."а";
		$word_many = mb_substr($word_one, 0, -1);
	}

	if(in_array($last_letter, ['е'])) {
		$word_few = mb_substr($word_one, 0, -1)."я";
		$word_many = mb_substr($word_one, 0, -1)."й";
	}

	if(in_array($last_letter, ['й'])) {
		$word_few = mb_substr($word_one, 0, -1)."я";
		$word_many = mb_substr($word_one, 0, -1)."ев";
	}

	if($word_one == 'день') {
		$word_few = 'дня';
		$word_many = 'дней';
	}

	$lastDigit = substr($int, -2);
	if(in_array($lastDigit, [11, 12, 13, 14, 15, 16 ,17 ,18, 19])) return $word_many;

	$lastDigit = substr($int, -1);
	if($lastDigit == 1) return $word_one;
	if(in_array($lastDigit, [2, 3, 4])) return $word_few;

	return $word_many;
}

function formatAndHideNumber ($number) :string
{
	$str = number_format($number, 0 , '.', ' ');
	return preg_replace('#\d#', '*', $str);
}








