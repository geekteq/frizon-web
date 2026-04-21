<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';

class FrizzeController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function overview(array $params): void
    {
        $this->render('overview');
    }

    public function journal(array $params): void
    {
        $this->render('journal');
    }

    public function receipts(array $params): void
    {
        $this->render('receipts');
    }

    public function servicePlan(array $params): void
    {
        $this->render('service');
    }

    public function equipment(array $params): void
    {
        $this->render('equipment');
    }

    public function manual(array $params): void
    {
        $this->render('manual');
    }

    private function render(string $activeTab): void
    {
        Auth::requireLogin();

        $pageTitle = 'Frizze';
        $vehicle = $this->vehicle();
        $statusCards = $this->statusCards();
        $journal = $this->journalItems();
        $servicePlan = $this->servicePlanItems();
        $equipment = $this->equipmentGroups();
        $manualSections = $this->manualSections();
        $budget = $this->budget();
        $tabs = $this->tabs();

        view('frizze/index', compact(
            'activeTab',
            'budget',
            'equipment',
            'journal',
            'manualSections',
            'pageTitle',
            'servicePlan',
            'statusCards',
            'tabs',
            'vehicle'
        ));
    }

    private function tabs(): array
    {
        return [
            'overview' => ['label' => 'Översikt', 'href' => '/adm/frizze'],
            'journal' => ['label' => 'Journal', 'href' => '/adm/frizze/journal'],
            'receipts' => ['label' => 'Kvitton', 'href' => '/adm/frizze/kvitton'],
            'service' => ['label' => 'Serviceplan', 'href' => '/adm/frizze/serviceplan'],
            'equipment' => ['label' => 'Utrustning', 'href' => '/adm/frizze/utrustning'],
            'manual' => ['label' => 'Manual', 'href' => '/adm/frizze/manual'],
        ];
    }

    private function vehicle(): array
    {
        return [
            'name' => 'Frizze',
            'model' => 'Adria Twin 600 SPT / SPT 600 Platinum',
            'year' => '2017',
            'base' => 'Citroën Jumper III',
            'engine' => '2.0 HDI / BlueHDi diesel',
            'registration' => 'ZLG267',
            'vin' => 'VF7YD3MFC12C73353',
            'odometer' => 'ca 100 000 km',
            'owner' => 'Mattias Pettersson',
            'purchased_at' => '2022-06-14',
        ];
    }

    private function statusCards(): array
    {
        return [
            [
                'label' => 'Nästa motorservice',
                'value' => 'April 2027',
                'meta' => 'Olja + oljefilter senast 12 månader eller ca 15 000 km efter 2026-04-02.',
                'state' => 'watch',
            ],
            [
                'label' => 'Gasol',
                'value' => 'April 2027',
                'meta' => 'Senast godkänd 2026-04-02 enligt EN 1949 hos Torvalla LCV.',
                'state' => 'ok',
            ],
            [
                'label' => 'Fukt / habitation',
                'value' => 'Att boka 2026',
                'meta' => 'Bodelkontroll saknas i nuvarande historik för 2026.',
                'state' => 'due',
            ],
            [
                'label' => 'Besiktning',
                'value' => 'Början av juni',
                'meta' => 'Bokad inom perioden juni-augusti.',
                'state' => 'ok',
            ],
            [
                'label' => 'Främre taklucka',
                'value' => 'Tätad',
                'meta' => 'Inväntar kraftigt regn/skyfall för verifiering.',
                'state' => 'watch',
            ],
            [
                'label' => 'Däck',
                'value' => 'Däckfirma bevakar',
                'meta' => 'Sommar- och vinterdäck förvaras hos däckfirma.',
                'state' => 'ok',
            ],
        ];
    }

    private function journalItems(): array
    {
        return [
            [
                'date' => '2026-04-02',
                'type' => 'Service',
                'title' => '100 000 km-service + gasoltest',
                'meta' => 'Torvalla LCV, ca 100 000 km, 3 743 kr',
                'details' => [
                    'Byte motorolja enligt PSA B71 2312 / B71 2290',
                    'Byte oljefilter',
                    'Nollställning serviceindikator',
                    'Kontroll av vätskenivåer',
                    'Felkodsläsning/diagnostik',
                    'Gasoltäthetskontroll enligt EN 1949',
                ],
            ],
            [
                'date' => '2026',
                'type' => 'Reparation',
                'title' => 'Främre taklucka tätad',
                'meta' => 'Väntar på skyfall för att verifiera resultatet',
                'details' => [
                    'Tvätt med Abnet',
                    'Sika Cleaner 205',
                    'Sika Primer 210',
                    'Sikaflex 221',
                ],
            ],
            [
                'date' => '2025-02-18',
                'type' => 'Service',
                'title' => 'Ny motor, kamrem, alla vätskor och filter',
                'meta' => 'Torvalla LCV, 85 927 km, AO 128299',
                'details' => [
                    'Ny motor installerad',
                    'Kamrem bytt',
                    'Alla vätskor bytta',
                    'Alla filter bytta',
                ],
            ],
            [
                'date' => '2025-02-14',
                'type' => 'Kontroll',
                'title' => 'Gasolprotokoll samt fukt/täthet',
                'meta' => 'Torvalla LCV, godkänt',
                'details' => [
                    'Gasol godkänd',
                    'Fukt- och täthetsprotokoll godkänt',
                ],
            ],
            [
                'date' => '2024-02-27',
                'type' => 'Service',
                'title' => 'Service hos Caravanhallen',
                'meta' => '73 650 km',
                'details' => [
                    'Service',
                    'Däckventiler',
                    'Bromsar kontrollerade/bytta',
                    'Gasoltäthet godkänd',
                ],
            ],
        ];
    }

    private function servicePlanItems(): array
    {
        return [
            [
                'year' => '2026',
                'km' => 'ca 100 000',
                'summary' => 'Motorservice och gasol är gjort. Kvar att komma ikapp är fukttest/habitation check om det inte redan är gjort.',
                'items' => ['Besiktning bokad i början av juni', 'Verifiera tätad taklucka vid skyfall'],
            ],
            [
                'year' => '2027',
                'km' => '~115 000',
                'summary' => 'Årlig service med extra fokus på bromsvätska och kupéfilter.',
                'items' => ['Olja + oljefilter', 'Diagnostik', 'Gasol', 'Fukttest', 'Habitation check', 'Bromsvätska', 'Kupéfilter'],
            ],
            [
                'year' => '2028',
                'km' => '~130 000',
                'summary' => 'Årlig service och större kontroll av bromsar/hjulupphängning vid behov.',
                'items' => ['Olja + oljefilter', 'Gasol', 'Fukt', 'Habitation', 'AC vid behov'],
            ],
            [
                'year' => '2030',
                'km' => '~160 000',
                'summary' => 'Nästa större filterservice och kylvätska.',
                'items' => ['Dieselfilter', 'Luftfilter', 'Kupéfilter', 'Bromsvätska', 'Kylvätska'],
            ],
            [
                'year' => '2035',
                'km' => '~235 000',
                'summary' => 'Kamremspaket enligt ny rem från 2025.',
                'items' => ['Kamrem', 'Spännare', 'Vattenpump'],
            ],
        ];
    }

    private function equipmentGroups(): array
    {
        return [
            'Basfordon' => [
                'Citroën Jumper III',
                '2.0 HDI / BlueHDi diesel',
                '6-växlad manuell',
                'Framhjulsdrift',
            ],
            'Bodel' => [
                'Truma Combi 4, gasol',
                'Fast säng med uppfällbar mittdel',
                'Kassetttoalett och dusch',
                'Thule Omnistor 6300 markis',
            ],
            'El' => [
                '2 x SunLux Lithium 100Ah',
                '40A DC/DC-laddare med boost till startbatteri',
                '120W solceller',
                'Victron 12V/30A solcellsregulator',
            ],
            'Hjul' => [
                'Sommar: 225/75 R16 Kumho på lättmetall',
                'Vinter: 225/75 R16 Nokian dubbfria på stålfälg',
                'Förvaras hos däckfirma som bevakar byte',
            ],
            'Tillbehör' => [
                'One Beam Air High lufttält',
                'Cadac Safari Chef 2',
                'Petromax Atago',
                'PC10 + PC5 gasolflaskor',
            ],
        ];
    }

    private function manualSections(): array
    {
        return [
            [
                'title' => 'Snabbsvar',
                'items' => [
                    ['label' => 'Nästa service', 'value' => 'April 2027 eller ca 15 000 km efter 2026-04-02'],
                    ['label' => 'Nästa gasol', 'value' => 'April 2027'],
                    ['label' => 'Nästa kamrem', 'value' => 'ca 2035 eller ca 235 000 km'],
                    ['label' => 'Rekommenderad olja', 'value' => 'PSA B71 2312 / PSA B71 2290'],
                ],
            ],
            [
                'title' => 'Dokument',
                'items' => [
                    ['label' => 'Fordonsidentifikation', 'value' => 'Chassi, reg.nr, besiktning, försäkring'],
                    ['label' => 'Servicehistorik', 'value' => 'Service, fakturor, reparationer'],
                    ['label' => 'Gasolcertifikat', 'value' => 'Gasolprotokoll och fukt/täthet'],
                    ['label' => 'Vintercamping', 'value' => 'Truma, FrostControl, tömning av vatten'],
                ],
            ],
        ];
    }

    private function budget(): array
    {
        return [
            'year' => '2027',
            'planned' => '8 000-12 000 kr',
            'buffer' => '15 000 kr',
            'lines' => [
                ['label' => 'Årlig service + gasol', 'amount' => '4 000-6 000 kr'],
                ['label' => 'Bromsvätska + kupéfilter/extra kontroll', 'amount' => '2 000-4 000 kr'],
                ['label' => 'Fukttest / habitation', 'amount' => '2 000-4 000 kr'],
            ],
        ];
    }
}
