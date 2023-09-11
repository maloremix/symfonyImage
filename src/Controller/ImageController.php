<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Контроллер для обработки изображений.
 */
class ImageController extends AbstractController
{
    /**
     * Отображает форму и обрабатывает отправку URL изображения.
     *
     * @Route('/image', name: 'app_image')
     * @param Request $request Запрос HTTP.
     * @return Response
     */
    #[Route('/image', name: 'app_image')]
    public function index(Request $request): Response
    {
        // Обработка отправки формы с URL
        $url = $request->request->get('url'); // Получаем URL из формы

        if ($url) {
            // Создаем HTTP-клиент
            $client = HttpClient::create();

            // Отправляем GET-запрос к указанному URL
            $response = $client->request('GET', $url);

            // Получаем содержимое страницы
            $content = $response->getContent();

            // Создаем объект Crawler для парсинга HTML
            $crawler = new Crawler($content);

            // Извлекаем изображения
            $images = [];
            $crawler->filter('img')->each(function ($node) use (&$images) {
                $src = $node->attr('src');
                // Если URL изображения абсолютный, добавьте его в список изображений
                if (filter_var($src, FILTER_VALIDATE_URL)) {
                    $images[] = $src;
                }
            });

            // Рассчитываем количество изображений и их суммарный размер
            $imageCount = count($images);
            $totalSize = $this->calculateTotalSize($images);

            return $this->render('image/result.html.twig', [
                'images' => $images,
                'imageCount' => $imageCount,
                'totalSize' => $totalSize,
            ]);
        }

        return $this->render('image/index.html.twig');
    }

    /**
     * Вычисляет суммарный размер изображений.
     *
     * @param array $images Список URL изображений.
     * @return string Суммарный размер в удобочитаемом виде.
     */
    private function calculateTotalSize(array $images): string
    {
        $totalSize = 0;
        foreach ($images as $image) {
            $headers = get_headers($image, 1);
            if (isset($headers['Content-Length'])) {
                $totalSize += (int)$headers['Content-Length'];
            }
        }

        return $this->formatBytes($totalSize);
    }

    /**
     * Форматирует размер в удобочитаемый вид.
     *
     * @param int $size Размер в байтах.
     * @param int $precision Количество знаков после запятой.
     * @return string Отформатированный размер.
     */
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}