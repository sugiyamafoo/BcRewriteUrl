<?php
declare(strict_types=1);

namespace BcRewriteUrl\Middleware;

use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RewriteUrlMiddleware implements MiddlewareInterface
{
    private const RESERVED = ['archives', 'index', 'tags', 'ajax_add_comment', 'captcha'];

    private static ?array $bases = null;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (strpos($path, '/baser/') === 0 || strpos($path, '/files/') === 0) {
            return $handler->handle($request);
        }
        $bases = $this->getBases();
        if (!$bases) {
            return $handler->handle($request);
        }

        foreach ($bases as $base) {
            $old = $base . 'archives/';

            // 1. 旧URL(/archives/付き)は新URLへ301(プレビューは除く)
            if (in_array($request->getMethod(), ['GET', 'HEAD'], true) && strpos($path, $old) === 0 && strlen($path) > strlen($old)) {
                $query = $request->getUri()->getQuery();
                parse_str($query, $q);
                if (!isset($q['preview']) && !isset($q['force'])) {
                    $location = $base . substr($path, strlen($old));
                    if ($query !== '') {
                        $location .= '?' . $query;
                    }
                    return (new Response())->withStatus(301)->withHeader('Location', $location);
                }
            }

            // 2. 新URLは内部で /archives/ 付きに読み替える
            if (strpos($path, $base) === 0 && $this->shouldRewrite(substr($path, strlen($base)))) {
                $newPath = $old . substr($path, strlen($base));
                $query = $request->getUri()->getQuery();
                $request = $request
                    ->withUri($request->getUri()->withPath($newPath))
                    ->withRequestTarget($newPath . ($query !== '' ? '?' . $query : ''));
                break;
            }
        }

        $response = $handler->handle($request);

        // 3. 出力中のリンクから /archives/ を外す
        $type = $response->getHeaderLine('Content-Type');
        if (!preg_match('#text/html|xml#i', $type) || !method_exists($response, 'withStringBody')) {
            return $response;
        }
        $search = [];
        $replace = [];
        foreach ($bases as $base) {
            $search[] = $base . 'archives/';
            $replace[] = $base;
        }
        $body = (string)$response->getBody();
        $new = str_replace($search, $replace, $body);
        if ($new === $body) {
            return $response;
        }
        return $response->withStringBody($new)->withoutHeader('Content-Length');
    }

    private function shouldRewrite(string $rest): bool
    {
        $first = explode('/', $rest, 2)[0];
        return $first !== ''
            && strpos($first, '.') === false
            && !in_array($first, self::RESERVED, true);
    }

    private function getBases(): array
    {
        if (self::$bases !== null) {
            return self::$bases;
        }
        self::$bases = [];
        try {
            $contents = TableRegistry::getTableLocator()->get('BaserCore.Contents');
            $rows = $contents->find()->select(['url'])
                ->where(['plugin' => 'BcBlog', 'type' => 'BlogContent'])->all();
            foreach ($rows as $row) {
                $url = rtrim((string)$row->url, '/') . '/';
                if ($url !== '/') {
                    self::$bases[] = $url;
                }
            }
            usort(self::$bases, fn($a, $b) => strlen($b) <=> strlen($a));
        } catch (\Throwable $e) {
            self::$bases = [];
        }
        return self::$bases;
    }
}
