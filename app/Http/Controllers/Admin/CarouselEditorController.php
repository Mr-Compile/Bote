<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class CarouselEditorController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
        $slide_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $slide = null;
        
        if ($slide_id > 0) {
            $stmt = $this->legacy()->prepare("SELECT * FROM carousel_slides WHERE slide_id = ?");
            $stmt->bind_param("i", $slide_id);
            $stmt->execute();
            $slide = $stmt->get_result()->fetch_assoc();
            
            if (!$slide) {
                $this->redirect('admin_settings.php');
            }
        }

        $data = get_defined_vars();

        $this->view('admin.carousel_editor', $data);
    }
}