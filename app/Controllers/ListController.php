<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/ItemList.php';
require_once dirname(__DIR__) . '/Models/ListItem.php';
require_once dirname(__DIR__) . '/Models/ListTemplate.php';

class ListController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function index(array $params): void
    {
        Auth::requireLogin();
        $listModel = new ItemList($this->pdo);
        $lists = $listModel->all();

        $grouped = ['checklist' => [], 'shopping' => []];
        foreach ($lists as $list) {
            $grouped[$list['list_type']][] = $list;
        }

        $templateModel = new ListTemplate($this->pdo);
        $templates = $templateModel->all();

        $pageTitle = 'Listor';
        view('lists/index', compact('grouped', 'templates', 'pageTitle'));
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $templateModel = new ListTemplate($this->pdo);
        $templates = $templateModel->all();
        $pageTitle = 'Ny lista';
        view('lists/create', compact('templates', 'pageTitle'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Listnamn krävs.');
            redirect('/listor/ny');
        }

        $listModel = new ItemList($this->pdo);
        $listId = $listModel->create([
            'scope_type'           => $_POST['scope_type'] ?? 'global',
            'scope_id'             => $_POST['scope_id'] ?: null,
            'list_type'            => $_POST['list_type'] ?? 'checklist',
            'title'                => $title,
            'based_on_template_id' => $_POST['template_id'] ?: null,
            'created_by'           => Auth::userId(),
        ]);

        // If created from template, populate items
        $templateId = (int) ($_POST['template_id'] ?? 0);
        if ($templateId > 0) {
            $templateModel = new ListTemplate($this->pdo);
            $templateModel->instantiate($templateId, $listId, $this->pdo);
        }

        flash('success', 'Listan har skapats!');
        redirect('/listor/' . $listId);
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $listModel = new ItemList($this->pdo);
        $list = $listModel->findById((int) $params['id']);
        if (!$list) { http_response_code(404); echo '<h1>Listan hittades inte</h1>'; return; }

        $itemModel = new ListItem($this->pdo);
        $items = $itemModel->findByList((int) $list['id']);

        $pageTitle = $list['title'];
        view('lists/show', compact('list', 'items', 'pageTitle'));
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $listModel = new ItemList($this->pdo);
        $list = $listModel->findById((int) $params['id']);
        if (!$list) { http_response_code(404); return; }

        $pageTitle = 'Redigera ' . $list['title'];
        view('lists/edit', compact('list', 'pageTitle'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $listModel = new ItemList($this->pdo);
        $list = $listModel->findById((int) $params['id']);
        if (!$list) { http_response_code(404); return; }

        $listModel->update((int) $list['id'], [
            'title'     => trim($_POST['title'] ?? $list['title']),
            'list_type' => $_POST['list_type'] ?? $list['list_type'],
        ]);

        flash('success', 'Listan har uppdaterats.');
        redirect('/listor/' . $params['id']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $listModel = new ItemList($this->pdo);
        $list = $listModel->findById((int) $params['id']);
        if ($list) {
            $listModel->delete((int) $list['id']);
            flash('success', 'Listan har tagits bort.');
        }
        redirect('/listor');
    }

    // --- Item endpoints ---

    public function addItem(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $text = trim($_POST['text'] ?? '');
        if ($text === '') {
            flash('error', 'Ange en text.');
            redirect('/listor/' . $params['id']);
        }

        $itemModel = new ListItem($this->pdo);
        $itemModel->add(
            (int) $params['id'],
            $text,
            trim($_POST['category'] ?? '') ?: null
        );

        flash('success', 'Punkt tillagd!');
        redirect('/listor/' . $params['id']);
    }

    public function toggleItem(array $params): void
    {
        Auth::requireLogin();

        $itemModel = new ListItem($this->pdo);
        $result = $itemModel->toggleDone((int) $params['itemId']);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function removeItem(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $itemModel = new ListItem($this->pdo);
        $item = $itemModel->findById((int) $params['itemId']);

        if ($item) {
            $listId = $item['list_id'];
            $itemModel->remove((int) $params['itemId']);
            flash('success', 'Punkten har tagits bort.');
            redirect('/listor/' . $listId);
        } else {
            redirect('/listor');
        }
    }

    public function reorderItems(array $params): void
    {
        Auth::requireLogin();

        $input = json_decode(file_get_contents('php://input'), true);
        $itemIds = $input['item_ids'] ?? [];

        if (!empty($itemIds)) {
            $itemModel = new ListItem($this->pdo);
            $itemModel->reorder((int) $params['id'], $itemIds);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    // --- Template endpoints ---

    public function templates(array $params): void
    {
        Auth::requireLogin();
        $templateModel = new ListTemplate($this->pdo);
        $templates = $templateModel->all();
        $pageTitle = 'Listmallar';
        view('lists/templates', compact('templates', 'pageTitle'));
    }

    public function createTemplate(array $params): void
    {
        Auth::requireLogin();
        $pageTitle = 'Ny listmall';
        view('lists/template-create', compact('pageTitle'));
    }

    public function storeTemplate(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Mallnamn krävs.');
            redirect('/listor/mallar/ny');
        }

        // Parse items from textarea (one per line)
        $rawItems = trim($_POST['items_text'] ?? '');
        $lines = array_filter(array_map('trim', explode("\n", $rawItems)));
        $items = array_map(fn($line) => ['text' => $line, 'category' => null], $lines);

        $templateModel = new ListTemplate($this->pdo);
        $templateModel->create([
            'list_type'   => $_POST['list_type'] ?? 'checklist',
            'title'       => $title,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'items_json'  => json_encode($items, JSON_UNESCAPED_UNICODE),
            'created_by'  => Auth::userId(),
        ]);

        flash('success', 'Mallen har skapats!');
        redirect('/listor/mallar');
    }

    public function deleteTemplate(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $templateModel = new ListTemplate($this->pdo);
        $template = $templateModel->findById((int) $params['id']);
        if ($template) {
            $templateModel->delete((int) $template['id']);
            flash('success', 'Mallen har tagits bort.');
        }
        redirect('/listor/mallar');
    }
}
