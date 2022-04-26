<?php


namespace App\Services;


use App\Exceptions\DadataNotFound;
use App\Libs\DaData;
use Illuminate\Support\Facades\DB;

class ReestrSEOservice
{
	protected $daData;

	public $isOldUri = false;

	public $newUri = null;

	public $ogrn = null;

	public function analizeUri(string $uri)
	{
		if(preg_match('#^inn(\d*)-(.*)#', $uri, $m)) {          // old link: inn1644047257-fin-invest
			$inn = $m[1];
			$name = $m[2];

			$ogrn = $this->findOgrnFromDB($inn);

			if(!$ogrn) {
                $list = (new DaData())->getCompanyList($inn);
                $ogrn = $list['suggestions']['0']['data']['ogrn'];
            }

            if(!$ogrn) abort(404);

            $ogrnEnd = substr($ogrn, -4);

			$this->newUri = "$inn-$name-$ogrnEnd";
			$this->isOldUri = true;
            $this->ogrn = $ogrn;

		} elseif (preg_match('#^ogrn(\d*)-(.*)#', $uri, $m)) {   // old link: ogrn315503000005960-kalyueva-viktoriya-alek
            $this->handleKnownOgrn($m[1], $m[2]);

		} elseif (preg_match('#^(\d+)-.*-(\d{4})$#', $uri, $matches)) {   // new link: 1644047257-fin-invest-4332
			$inn = $matches[1];
			$ogrnEnd = $matches[2];

            $ogrn = $this->findOgrnFromDB($inn, $ogrnEnd);

            if(!$ogrn) {
                try {
                    $list = (new DaData())->getCompanyList($inn);
                } catch (DadataNotFound $e) {
                    abort(404);
                }

                foreach ($list['suggestions'] as $company) {
                    if(preg_match("#$ogrnEnd$#", $company['data']['ogrn'])) {
                        $ogrn = $company['data']['ogrn'];
                        break;
                    }
                }
            }

            if(!$ogrn) abort(404);

			$this->newUri = $uri;
			$this->isOldUri = false;
			$this->ogrn = $ogrn;

		} elseif (preg_match('#^(\d{10,13})-(.*)#', $uri, $matches)) {         // old link: 1097746841554-bozon
            $this->handleKnownOgrn($matches[1], $matches[2]);
		}
	}

	private function handleKnownOgrn($ogrn, $name) {
        $ogrnEnd = substr($ogrn, -4);

        $dbEntitiy = DB::table('entities')
            ->where('ogrn', $ogrn)
            ->first();

        $DBinn = $dbEntitiy->inn ?? null;

        $inn = $DBinn ?: (new DaData())->getCompanyArray($ogrn)['inn'];

        $this->newUri = "$inn-$name-$ogrnEnd";
        $this->isOldUri = true;
        $this->ogrn = $ogrn;
    }

    private function findOgrnFromDB($inn, $ogrnEnd=null)
    {
        $dbEntities = DB::table('entities')
            ->where('inn', (string) $inn)
            ->get();

        if(!$dbEntities->count()) return null;

        if($ogrnEnd) {
            foreach ($dbEntities as $row) {
                if(preg_match("#$ogrnEnd$#", (string) $row->ogrn)) {
                    return $row->ogrn;
                }
            }
        } else {
            return $dbEntities->first()->ogrn;
        }
    }

	public function getEntityIndexSeo($ogrn, array $daDataCompany=null)
	{
		$this->daData = $daDataCompany;

		return $this->getEntityIndexFromDB($ogrn) ?: $this->createEntityIndex($ogrn);
	}

    protected function getEntityIndexFromDB($ogrn)
    {
        $seo = Db::table('entities_seo')->where('ogrn', $ogrn)->first();
        if ($seo) {
            unset($seo->ogrn);
            return (array) $seo;
        }

        return null;
    }

    protected function createEntityIndex($ogrn) :array
    {
    	if(!$this->daData) $this->daData = (new DaData(true))->getCompanyArray($ogrn, true);

		$inn = $this->daData['inn'];

		$name = $this->generateName();
		$address = $this->generateAddress();
		$manager = $this->generateManager();

        $city = ($this->daData['address']['data']['city']) ? ', '.$this->daData['address']['data']['city'] : '';
        $about = ($this->daData['type'] == 'LEGAL') ? 'о компании' : 'об ИП';
        $title = "$name$city - проверка и отзывы $about по ИНН $inn, ОГРН $ogrn";
        trim($title, '\,\ ');

        $keywords = "$name, $address, ИНН $inn, ОГРН $ogrn, проверка контрагента по ИНН $inn, $manager";
        $keywords = trim($keywords, '\,\ ');

		$description = "$name - ИНН $inn, $manager $address - отзывы, контакты и проверка компании по базам Fek.ru";

		$uri = $this->generateUri();

        $arrToSave = [
            'ogrn' => $ogrn,
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
			'uri' => $uri
        ];

        DB::table('entities_seo')->insert($arrToSave);

        return $arrToSave;
    }


    protected function generateName() :string
	{
		if($this->daData['type'] == 'LEGAL') {
			$name_raw = $this->daData['name']['short_with_opf'] ? mb_strtolower($this->daData['name']['short_with_opf']) : mb_strtolower($this->daData['name']['full_with_opf']);

			$opf_short = $this->daData['opf']['short'] ? mb_strtolower($this->daData['opf']['short']) : '';
			$opf_full = $this->daData['opf']['full'] ? mb_strtolower($this->daData['opf']['full']) : '';

			$name_raw = str_replace([$opf_short, $opf_full], $this->daData['opf']['short'], $name_raw );

			if(preg_match('$"(.*)"$mu', $name_raw, $m)) {
				$name = str_replace($m[1], mb_ucfirst($m[1]), $name_raw);
			} else {
				$name = $name_raw;
			}

			$opf_short = $this->daData['opf']['short'];
			if(! preg_match("#^$opf_short#", $name)) $name = mb_ucfirst($name, true);
		}

		if($this->daData['type'] == 'INDIVIDUAL') {
			$name = $this->daData['name']['short_with_opf'] ?? $this->daData['name']['full'];
		}

		return $name;
	}

	protected function generateAddress() :string
	{
		$addr = $this->daData['address']['data'];

		$city = $addr['city_with_type'] ? $addr['city_with_type'].', ' : $addr['region_with_type'].', '.$addr['settlement_with_type'].', ';
		$street = $addr['street_with_type'] ? $addr['street_with_type']. ', ' : '';
		$house_type = $addr['house_type_full'] ? $addr['house_type_full']. ' ' : '';
		$house_num = $addr['house'] ? $addr['house'] : '';

		$address = "$city$street$house_type$house_num";
		$address = trim($address, '\,\ ');

		return $address;
	}

	protected function generateManager()
	{
		if($this->daData['type'] == 'LEGAL'&&$this->daData['management']) {
			$manager_name = $this->daData['management']['name'];
			$manager_position = mb_convert_case($this->daData['management']['post'], MB_CASE_TITLE, 'UTF-8');
			$manager = "$manager_position $manager_name,";

			if(strlen($manager) > 70 ) $manager = null;
		} else {
			$manager = null;
		}

		return $manager;
	}

	public function generateUri(array $companyArr=[]) :string
	{
		$company = $companyArr ?: $this->daData;

		if($company['type'] == 'LEGAL') {
			$name = $company['name']['short'] ?: $company['name']['full'];
			$name = cyrToLat($name);
			$name = str_replace(['"', "'", '+'], '', $name);
			$name = trim($name);
			$name = str_replace(' ', '-', $name);
		} else {
			$name = $company['name']['full'];
			$name = cyrToLat($name);
			preg_match('#^(\w*)#', $name, $m);
			$name = $m[1];
		}

		$name = mb_convert_case($name, MB_CASE_LOWER, 'UTF-8');
		$name = preg_replace('#\(.*\)#', '', $name);
		$name = str_replace('.', '-', $name);

		$inn = $company['inn'];
		$ogrnEnd = substr($company['ogrn'], -4);

		$uri = "$inn-$name-$ogrnEnd";
		$uri = preg_replace('#--#', '-', $uri);

		return $uri;
	}
}
