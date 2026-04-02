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
            redirect('/adm/listor/ny');
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
        redirect('/adm/listor/' . $listId);
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
        redirect('/adm/listor/' . $params['id']);
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
        redirect('/adm/listor');
    }

    // --- Item endpoints ---

    public function addItem(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $text     = trim($_POST['text'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: null;
        $isAjax   = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if ($text === '') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Ange en text.']);
                return;
            }
            flash('error', 'Ange en text.');
            redirect('/adm/listor/' . $params['id']);
        }

        $itemModel = new ListItem($this->pdo);
        $itemId = $itemModel->add((int) $params['id'], $text, $category);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'item'    => ['id' => $itemId, 'text' => $text, 'category' => $category],
            ]);
            return;
        }

        flash('success', 'Punkt tillagd!');
        redirect('/adm/listor/' . $params['id']);
    }

    public function toggleItem(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

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
            redirect('/adm/listor/' . $listId);
        } else {
            redirect('/adm/listor');
        }
    }

    public function reorderItems(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

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
            redirect('/adm/listor/mallar/ny');
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
        redirect('/adm/listor/mallar');
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
        redirect('/adm/listor/mallar');
    }
}
