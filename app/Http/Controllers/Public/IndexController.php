<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\BaseController;

final class IndexController extends BaseController
{
    protected string $basePath = '';

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return [];
    }

    public function render(): void
    {
        $systemName = $this->setting('barangay_name', 'BoteBank');
        $systemLogo = $this->setting('barangay_logo', '');
        $bannerImage = $this->setting('system_banner', '');
        $bannerTitle = $this->setting('banner_title', 'Welcome to BoteBank');
        $bannerSubtitle = $this->setting('banner_subtitle', 'Turn your recyclables into rewards and help build a greener community');
        $bannerEnabled = $this->setting('banner_enabled', '1');

        $carouselSlides = $this->carouselSlides($bannerTitle, $bannerSubtitle);

        $this->view->render('public.index', [
            'title' => $systemName . ' - Tracking & Reward System',
            'system_name' => $systemName,
            'system_logo' => $systemLogo,
            'banner_image' => $bannerImage,
            'banner_title' => $bannerTitle,
            'banner_subtitle' => $bannerSubtitle,
            'banner_enabled' => $bannerEnabled,
            'carousel_slides' => $carouselSlides,
        ], null);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function carouselSlides(string $bannerTitle, string $bannerSubtitle): array
    {
        try {
            $rows = $this->container->database->query(
                'SELECT * FROM carousel_slides WHERE is_active = 1 ORDER BY display_order, slide_id',
                [],
                '',
            );

            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable $e) {
            error_log('Carousel slides error: ' . $e->getMessage());
        }

        return [
            [
                'slide_id' => 1,
                'title' => $bannerTitle,
                'subtitle' => $bannerSubtitle,
                'image' => null,
                'button_text' => 'Get Started',
                'button_link' => 'register.php',
            ],
            [
                'slide_id' => 2,
                'title' => 'Earn Points for Recycling',
                'subtitle' => 'Every bottle, every can, every recyclable item you contribute earns you valuable points that can be redeemed for exciting rewards!',
                'image' => null,
                'button_text' => 'Learn More',
                'button_link' => '#about',
            ],
            [
                'slide_id' => 3,
                'title' => 'Join Our Green Community',
                'subtitle' => 'Be part of a movement that\'s making a real difference. Together, we can build a sustainable future for our barangay.',
                'image' => null,
                'button_text' => 'Register Now',
                'button_link' => 'register.php',
            ],
        ];
    }
}
