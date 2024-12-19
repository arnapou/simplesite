<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Admin;

use Arnapou\Ensure\Enforce;
use Arnapou\Ensure\Ensure;
use Arnapou\Psr\Psr15HttpHandlers\Routing\Endpoint\Endpoint;
use Arnapou\Psr\Psr15HttpHandlers\Routing\Route;
use Arnapou\Psr\Psr7HttpMessage\FileResponse;
use Arnapou\Psr\Psr7HttpMessage\Header\ContentDisposition;
use Arnapou\Psr\Psr7HttpMessage\MimeType;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError as Error;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\Zip\Psr\Prs7ZipResponseStream;
use Arnapou\Zip\Writing\Zipped\ZippedFile;
use Arnapou\Zip\ZipReader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class AdminMainController extends AdminController
{
    public const string HOME = 'admin_home';
    public const string MAIN = 'admin_listing';

    protected function getEndpoints(): array
    {
        $common = static fn (Route $route) => $route
            ->setMethods('GET', 'POST')
            ->setRequirement('dir', '[A-Za-z0-9_-]+');

        return [
            new Endpoint(new Route('/', self::HOME), $this->routeListing(...)),
            new Endpoint(new Route('/redirects', 'admin_redirects')->setMethods('GET', 'POST'), $this->routeRedirects(...)),
            new Endpoint($common(new Route('{dir}/', self::MAIN)), $this->routeListing(...)),
            new Endpoint($common(new Route('{dir}/delete', 'admin_delete')), $this->routeDelete(...)),
            new Endpoint($common(new Route('{dir}/download', 'admin_download')), $this->routeDownload(...)),
            new Endpoint($common(new Route('{dir}/upload', 'admin_upload')), $this->routeUpload(...)),
            new Endpoint($common(new Route('{dir}/rename', 'admin_rename')), $this->routeRename(...)),
            new Endpoint($common(new Route('{dir}/createFile', 'admin_create_file')), $this->routeCreateFile(...)),
            new Endpoint($common(new Route('{dir}/createFolder', 'admin_create_folder')), $this->routeCreateFolder(...)),
        ];
    }

    private function routeListing(ServerRequestInterface $request, string $dir = ''): mixed
    {
        return $this->firewall(function () use ($request, $dir) {
            $context = ['node' => $node = $this->node($dir)];

            return match ($request->getMethod()) {
                'GET' => match (true) {
                    $node->dir => $this->render('listing.twig', $context),
                    $node->canEdit() => $this->render('form-edit.twig', $context),
                    default => throw Problem::fromStatus(Error::Forbidden),
                },
                'POST' => match ($params = $this->requestParams($request)) {
                    null => $this->renderInvalidCsrf('form-edit.twig', $context),
                    default => match (true) {
                        $node->canEdit() => $this->doEdit($node, $params),
                        default => throw Problem::fromStatus(Error::Forbidden),
                    },
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    private function routeRedirects(ServerRequestInterface $request): mixed
    {
        return $this->firewall(function () use ($request) {
            return match ($request->getMethod()) {
                'GET' => $this->render('form-redirects.twig', ['source' => $this->getAdminRedirectsAsSource()]),
                'POST' => match ($params = $this->requestParams($request)) {
                    null => $this->renderInvalidCsrf('form-redirects.twig', ['source' => $this->getAdminRedirectsAsSource()]),
                    default => $this->doRedirects($params),
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    private function routeDelete(ServerRequestInterface $request, string $dir): mixed
    {
        return $this->firewall(function () use ($request, $dir) {
            $context = ['node' => $node = $this->node($dir)];

            return match ($request->getMethod()) {
                'GET' => match (true) {
                    $node->canDelete() => $this->render('form-delete.twig', $context),
                    default => throw Problem::fromStatus(Error::Forbidden),
                },
                'POST' => match ($this->requestParams($request)) {
                    null => $this->renderInvalidCsrf('form-delete.twig', $context),
                    default => match (true) {
                        $node->canDelete() => $this->doDelete($node),
                        default => throw Problem::fromStatus(Error::Forbidden),
                    },
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    private function routeDownload(ServerRequestInterface $request, string $dir): mixed
    {
        return $this->firewall(function () use ($request, $dir) {
            $node = $this->node($dir);

            return match ($request->getMethod()) {
                'GET' => match (true) {
                    $node->canDownload() => $this->doDownload($node),
                    default => throw Problem::fromStatus(Error::Forbidden),
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    private function routeUpload(ServerRequestInterface $request, string $dir = ''): mixed
    {
        return $this->firewall(function () use ($request, $dir) {
            $context = ['node' => $node = $this->node($dir)];

            return match ($request->getMethod()) {
                'GET' => match (true) {
                    $node->canUpload() => $this->render('form-upload.twig', $context),
                    default => throw Problem::fromStatus(Error::Forbidden),
                },
                'POST' => match ($params = $this->requestParams($request)) {
                    null => $this->renderInvalidCsrf('form-upload.twig', $context),
                    default => match (true) {
                        $node->canUpload() => $this->doUpload($node, $params, $request->getUploadedFiles()),
                        default => throw Problem::fromStatus(Error::Forbidden),
                    },
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    private function routeRename(ServerRequestInterface $request, string $dir): mixed
    {
        return $this->firewall(function () use ($request, $dir) {
            $context = ['node' => $node = $this->node($dir)];

            return match ($request->getMethod()) {
                'GET' => match (true) {
                    $node->canRename() => $this->render('form-rename.twig', $context),
                    default => throw Problem::fromStatus(Error::Forbidden),
                },
                'POST' => match ($params = $this->requestParams($request)) {
                    null => $this->renderInvalidCsrf('form-rename.twig', $context),
                    default => match (true) {
                        $node->canRename() => $this->doRename($node, $params),
                        default => throw Problem::fromStatus(Error::Forbidden),
                    },
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    private function routeCreateFile(ServerRequestInterface $request, string $dir): mixed
    {
        return $this->routeCreate($request, $dir, false);
    }

    private function routeCreateFolder(ServerRequestInterface $request, string $dir): mixed
    {
        return $this->routeCreate($request, $dir, true);
    }

    private function routeCreate(ServerRequestInterface $request, string $dir, bool $folder): mixed
    {
        return $this->firewall(function () use ($request, $dir, $folder) {
            $node = $this->node($dir);

            return match ($request->getMethod()) {
                'GET' => match (true) {
                    $node->canCreate() => $this->render('form-create.twig', ['node' => $node, 'folder' => $folder]),
                    default => throw Problem::fromStatus(Error::Forbidden),
                },
                'POST' => match ($params = $this->requestParams($request)) {
                    null => $this->renderInvalidCsrf('form-create.twig', ['node' => $node, 'folder' => $folder]),
                    default => match (true) {
                        $node->canCreate() => $this->doCreate($node, $params, $folder),
                        default => throw Problem::fromStatus(Error::Forbidden),
                    },
                },
                default => throw Problem::fromStatus(Error::MethodNotAllowed),
            };
        });
    }

    /**
     * @param array<mixed> $params
     */
    private function doEdit(AdminNode $node, array $params): Response
    {
        $source = str_replace("\r", '', Ensure::string($params['source']));

        if (\in_array($node->ext, ['yml', 'yaml'], true) && \is_string($validation = $this->helper->yamlValidate($source))) {
            $this->session->flashMessage = $validation;

            return $this->render('form-edit.twig', ['node' => $node, 'source' => $source]);
        }

        if ('json' === $node->ext && false === json_validate($source)) {
            $this->session->flashMessage = 'The JSON is not valid.';

            return $this->render('form-edit.twig', ['node' => $node, 'source' => $source]);
        }

        file_put_contents($node->path, $source, LOCK_EX);
        $this->session->flashMessage = \sprintf('The file "%s" was saved.', $node->name());

        return $this->redirect($this->adminUrl($node->parent()));
    }

    /**
     * @param array<mixed> $params
     */
    private function doRedirects(array $params): Response
    {
        $source = Ensure::string($params['source']);

        if (\is_string($validation = $this->helper->yamlValidate($source))) {
            $this->session->flashMessage = $validation;

            return $this->render('form-redirects.twig', ['source' => $source]);
        }

        $decoded = $this->helper->yamlDecode($source);
        $decoded = '' === $decoded || null === $decoded ? [] : $decoded;
        if (!\is_array($decoded)) {
            $this->session->flashMessage = 'The YAML must be a list.';

            return $this->render('form-redirects.twig', ['source' => $source]);
        }

        try {
            $this->admin->setRedirects($decoded);
        } catch (\Throwable $e) {
            $this->session->flashMessage = $e->getMessage();

            return $this->render('form-redirects.twig', ['source' => $source]);
        }

        $this->session->flashMessage = 'The redirects were saved.';

        return $this->redirectToRoute(self::HOME);
    }

    private function doDelete(AdminNode $node): Response
    {
        if ($node->dir) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($node->path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            $count = 0;
            /** @var iterable<\SplFileInfo> $files */
            foreach ($files as $fileinfo) {
                if ($fileinfo->isDir()) {
                    rmdir($fileinfo->getPathname());
                } else {
                    ++$count;
                    unlink($fileinfo->getPathname());
                }
            }

            rmdir($node->path);
            $this->session->flashMessage = \sprintf('The folder "%s" and %d files were deleted.', $node->name(), $count);
        } else {
            unlink($node->path);
            $this->session->flashMessage = \sprintf('The file "%s" was deleted.', $node->name());
        }

        return $this->redirect($this->adminUrl($node->parent()));
    }

    /**
     * @param array<mixed> $params
     */
    private function doRename(AdminNode $node, array $params): Response
    {
        $context = ['node' => $node];

        if ('' === ($name = trim(Ensure::string($params['name'])))) {
            $this->session->flashMessage = 'The name cannot be empty.';

            return $this->render('form-rename.twig', $context);
        }

        $renamed = $node->rename($name);

        if ($node->path === $renamed->path) {
            $this->session->flashMessage = \sprintf('The file "%s" was not renamed: it is the same!', basename($name));

            return $this->redirect($this->adminUrl($renamed->parent()));
        }

        if ($renamed->exists()) {
            $this->session->flashMessage = \sprintf('The name "%s" is already used in the folder.', $name);

            return $this->render('form-rename.twig', $context);
        }

        if (!is_dir(\dirname($renamed->path))) {
            $this->session->flashMessage = \sprintf('The target folder for "%s" does not exist.', $name);

            return $this->render('form-rename.twig', $context);
        }

        rename($node->path, $renamed->path);
        $this->session->flashMessage = \sprintf('The file "%s" was renamed to "%s".', $node->name(), $renamed->name());

        return $this->redirect($this->adminUrl($renamed->parent()));
    }

    /**
     * @param array<mixed> $params
     */
    private function doCreate(AdminNode $node, array $params, bool $folder): Response
    {
        if ('' === ($name = trim(Ensure::string($params['name'])))) {
            $this->session->flashMessage = 'The name cannot be empty.';

            return $this->render('form-create.twig', ['node' => $node, 'folder' => $folder]);
        }

        $created = $node->create($name);
        if ($created->exists()) {
            $this->session->flashMessage = \sprintf('The name "%s" is already used in the folder.', $name);

            return $this->render('form-create.twig', ['node' => $node, 'folder' => $folder]);
        }

        return match ($folder) {
            false => $this->doCreateFile($created, $name),
            true => $this->doCreateFolder($created, $name),
        };
    }

    private function doCreateFile(AdminNode $created, string $name): Response
    {
        $this->mkdir($created);
        touch($created->path);
        $this->session->flashMessage = \sprintf('The file "%s" was created.', $name);

        return $created->canEdit()
            ? $this->redirect($this->adminUrl($created))
            : $this->redirect($this->adminUrl($created->parent()));
    }

    private function doCreateFolder(AdminNode $created, string $name): Response
    {
        if (!mkdir($created->path, 0o777, true) && !is_dir($created->path)) {
            throw new Problem(\sprintf('Directory "%s" was not created', $created->path));
        }
        $this->session->flashMessage = \sprintf('The folder "%s" was created.', $name);

        return $this->redirect($this->adminUrl($created));
    }

    private function doDownload(AdminNode $node): Response
    {
        session_write_close();

        if ($node->dir) {
            $filename = $node->name() . '.zip';
            $response = new FileResponse($stream = new Prs7ZipResponseStream(), MimeType::detect($filename));
            $this->doDownloadAddNode($stream, $node, \strlen($node->path) + 1);
        } else {
            $filename = $node->name();
            $response = FileResponse::fromFilename($node->path);
        }

        return $response->withHeader(ContentDisposition::attachment($filename));
    }

    private function doDownloadAddNode(Prs7ZipResponseStream $stream, AdminNode $node, int $trim): void
    {
        if (!$node->isForbidden()) {
            if ($node->dir) {
                foreach ($node->list() as $item) {
                    $this->doDownloadAddNode($stream, $item, $trim);
                }
            } else {
                $item = new ZippedFile(
                    Ensure::nonEmptyString($node->path),
                    Ensure::nonEmptyString(substr($node->path, $trim)),
                );
                $stream->addZipItem($item);
            }
        }
    }

    /**
     * @param array<mixed> $params
     */
    private function doUpload(AdminNode $node, array $params, mixed $uploadedFiles): ResponseInterface
    {
        $upload = new AdminUpload(Enforce::bool($params['isZip'] ?? false));

        $this->doUploadFiles($node, $uploadedFiles, $upload);

        if (!$upload->isOk()) {
            return $this->render('form-upload.twig', ['node' => $node, 'upload' => $upload]);
        }

        $this->session->flashMessage = \sprintf('%d files were uploaded.', \count($upload->success));

        return $this->redirect($this->adminUrl($node));
    }

    private function doUploadFiles(AdminNode $node, mixed $files, AdminUpload $upload): void
    {
        if (\is_array($files)) {
            foreach ($files as $item) {
                $this->doUploadFiles($node, $item, $upload);
            }

            return;
        }

        if (!$files instanceof UploadedFileInterface) {
            return;
        }

        // @see https://www.php.net/manual/en/features.file-upload.errors.php
        $error = match ($files->getError()) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => null,
        };

        $filename = $files->getClientFilename();
        if (null === $filename) {
            $upload->addError('?', 'Undefined filename.');
        } elseif (null !== $error) {
            $upload->addError($filename, $error);
        } else {
            try {
                $this->doUploadFile($files, $node->create($filename), $upload);
            } catch (\Throwable $e) {
                $upload->addError($filename, $e->getMessage());
            }
        }
    }

    private function doUploadFile(UploadedFileInterface $file, AdminNode $target, AdminUpload $upload): void
    {
        if ($target->isForbidden()) {
            $upload->addError($target->path, 'Forbidden.');
        } elseif ($upload->unzip && 'zip' === $target->ext) {
            // https://www.php.net/stream_get_meta_data
            $metadata = Ensure::array($file->getStream()->getMetadata());
            $tempFile = Ensure::nullableString($metadata['uri'] ?? null);
            if (null === $tempFile || !is_file($tempFile)) {
                throw new \Exception('Undefined uploaded zip temporary filename.');
            }

            $reader = new ZipReader($tempFile);
            $detail = '[' . $target->name() . '] ';
            foreach ($reader->getFiles() as $zippedFile) {
                try {
                    $zippedFileTarget = $target->parent()->create($zippedFile->getName());
                    $existed = $zippedFileTarget->exists();

                    $this->mkdir($zippedFileTarget);
                    file_put_contents($zippedFileTarget->path, $zippedFile->getContent(), LOCK_EX);

                    if ($existed) {
                        $upload->addWarning($zippedFile->getName(), $detail . 'Overridden.');
                    } else {
                        $upload->addSuccess($zippedFile->getName(), $detail . 'Upload OK.');
                    }
                } catch (\Throwable $e) {
                    $upload->addError($zippedFile->getName(), $detail . $e->getMessage());
                }
            }
        } elseif ($target->exists()) {
            $file->moveTo($target->path);
            $upload->addWarning($target->name(), 'Overridden.');
        } else {
            $file->moveTo($target->path);
            $upload->addSuccess($target->name(), 'Upload OK.');
        }
    }

    private function mkdir(AdminNode $node): void
    {
        $dir = $node->dir ? $node->path : \dirname($node->path);
        if (!is_dir($dir) && !mkdir($dir, 0o777, true) && !is_dir($dir)) {
            throw new Problem(\sprintf('Directory "%s" was not created', $dir));
        }
    }

    private function getAdminRedirectsAsSource(): string
    {
        return [] !== ($redirects = $this->admin->getRedirects()) ? $this->helper->yamlEncode($redirects)
            : <<<TEXT
                #
                # The redirects list is empty.
                # Below is an *example* of the expected YAML format.
                #

                - from: some/non-existent/page1
                  link: target/of/the/redirect
                  
                - from: some/non-existent/page2
                  link: https://some.external.site/

                TEXT;
    }
}
