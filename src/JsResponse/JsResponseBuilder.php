<?php

namespace App\JsResponse;

use ReflectionClass;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class JsResponseBuilder implements \Countable
{
    public const CALL_JQUERY_WEBCOMPONENT = 'call_jquery_webcomponent';
    public const CALL_JS_FUNCTION = 'call_js_function';
    public const CALL_RELOAD_DATATABLE = 'call_reload_datatable';
    public const CALL_RELOAD_PAGE = 'call_reload_page';
    public const HIDE_SPINNER = 'hide_spinner';

    public const EVAL = 'eval';
    public const REDIRECT = 'redirect';
    public const RELOAD = 'reload';
    public const UPDATE_HTML = 'update';
    public const REMOVE_HTML = 'remove';

    public const SHOW_TOAST = 'show_toast';
    public const SHOW_NOTIFY = 'show_notify';

    public const SHOW_MODAL = 'show_modal';
    public const CLOSE_MODAL = 'close_modal';
    public const SHOW_MODAL_FORM = 'show_modal_form';

    public const SHOW_OFFCANVAS = 'show_offcanvas';
    public const CLOSE_OFFCANVAS = 'close_offcanvas';

    public const CALL_WEBCOMPONENT = 'call_webcomponent';

    public const DOWNLOAD = 'download';

    private TranslatorInterface $translator;
    private RouterInterface $router;
    private Environment $twig;

    private array $messages = [];

    /**
     * JsResponseBuilder constructor.
     */
    public function __construct(TranslatorInterface $translator, RouterInterface $router, Environment $twig)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->twig = $twig;
    }

    /**
     * @param string $selector
     * @param string $method
     * @param mixed ...$methodParams
     * @return self
     */
    public function callJqueryWebComponent(string $selector, string $method, ...$methodParams): self
    {
        return $this->add(self::CALL_JQUERY_WEBCOMPONENT, [
            'selector' => $selector,
            'method' => $method,
            'method_params' => $methodParams
        ]);
    }

    /**
     * @param string $function
     * @param mixed ...$arguments
     * @return self
     */
    public function callJSFunction(string $function, ...$arguments): self
    {
        return $this->add(self::CALL_JS_FUNCTION, [
            'function' => $function,
            'arguments' => $arguments
        ]);
    }

    public function hideSpinner(): self
    {
        return $this->add(self::HIDE_SPINNER);
    }

    public function reloadPage(): self
    {
        return $this->add(self::CALL_RELOAD_PAGE);
    }

    // DataTable actions
    public function reloadDataTable(string $id): self
    {
        return $this->add(self::CALL_RELOAD_DATATABLE, [
            'selector' => "#" . $id,
        ]);
    }


    // Modal actions
    public function modalFormHtml(string $html): self
    {
        return $this->add(self::SHOW_MODAL_FORM, [
            'value' => $html,
        ]);
    }


    public function modalForm(string $template, array $context = []): self
    {
        try {
            $html = $this->twig->render($template, $context);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $html = $e->getMessage();
            $this->toastError($e->getMessage());
        }
        return $this->modalFormHtml($html);
    }

    public function add(string $action, array $params = []): self
    {
        $this->messages[] = new JsMessage($action, $params);

        return $this;
    }

    public function clear(): self
    {
        $this->messages = [];

        return $this;
    }

    public function getResponse(): JsResponse
    {
        uasort($this->messages, function (JsMessage $a, JsMessage $b) {
            return $a->compare($b);
        });

        return new JsResponse($this->messages);
    }

    public function count(): int
    {
        return count($this->messages);
    }

    // Misc

    public function download(string $content, string $filename = null): self
    {
        return $this->add(self::DOWNLOAD, [
            'content' => $content,
            'filename' => $filename
        ]);
    }

    // Toast actions

    /**
     * @param string $type
     * @param string|TranslatableMessage $text
     * @param TranslatableMessage|string|null $title
     * @param array $options
     * @return self
     */
    public function toast(string $type, TranslatableMessage|string $text, $title = null, array $options = []): self
    {
        return $this->add(self::SHOW_TOAST, [
            'type' => $type,
            'text' => $text instanceof TranslatableMessage ? $text->trans($this->translator) : $text,
            'title' => $title instanceof TranslatableMessage ? $title->trans($this->translator) : $title,
            'options' => $options
        ]);
    }

    /**
     * @param string $type
     * @param TranslatableMessage|string $text
     * @param null $title
     * @param array $options
     * @return self
     */
    public function notify(string $type, TranslatableMessage|string $text, $title = null, array $options = []): self
    {
        return $this->add(self::SHOW_NOTIFY, [
            'type' => $type,
            'text' => $text instanceof TranslatableMessage ? $text->trans($this->translator) : $text,
            'title' => $title instanceof TranslatableMessage ? $title->trans($this->translator) : $title,
            'options' => $options
        ]);
    }

    public function toastInfo(string $text, ?string $title = null): self
    {
        return $this->toast('info', $text, $title);
    }

    public function toastSuccess(string $text, ?string $title = null): self
    {
        return $this->toast('success', $text, $title);
    }

    public function toastWarning(string $text, ?string $title = null): self
    {
        return $this->toast('warning', $text, $title);
    }

    public function toastError(string $text, ?string $title = null): self
    {
        return $this->toast('error', $text, $title);
    }

    // Nav actions

    public function redirectToRoute(string $route, array $params = []): self
    {
        return $this->redirect($this->router->generate($route, $params));
    }

    public function redirect(string $url): self
    {
        return $this->add(self::REDIRECT, [
            'value' => $url,
        ]);
    }

    public function reload(): self
    {
        return $this->add(self::RELOAD);
    }

    // Eval actions

    public function eval(string $js): self
    {
        return $this->add(self::EVAL, [
            'value' => $js,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function script(string $template): self
    {
        return $this->eval($this->twig->render($template));
    }

    // Html actions

    public function updateHtml(string $cssSelector, string $html): self
    {
        return $this->add(self::UPDATE_HTML, [
            'value' => $html,
            'selector' => $cssSelector,
        ]);
    }

    public function update(string $cssSelector, string $template, array $context = []): self
    {
        return $this->updateHtml($cssSelector, $this->twig->render($template, $context));
    }

    public function remove(string $cssSelector): self
    {
        return $this->add(self::REMOVE_HTML, [
            'selector' => $cssSelector,
        ]);
    }

    // Modal actions

    public function modalHtml(string $html): self
    {
        return $this->add(self::SHOW_MODAL, [
            'value' => $html,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function modal(string $template, array $context = []): self
    {
        try {
            $html = $this->modalHtml($this->twig->render($template, $context));
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->toastError($e->getMessage());

            throw $e;
        }

        return $html;
    }

    public function closeModal(): self
    {
        return $this->add(self::CLOSE_MODAL);
    }

    // Offcanvas actions

    public function offcanvasHtml(string $html): self
    {
        return $this->add(self::SHOW_OFFCANVAS, [
            'value' => $html,
        ]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function offcanvas(string $template, array $context = []): self
    {
        try {
            $html = $this->offcanvasHtml($this->twig->render($template, $context));
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->toastError($e->getMessage());

            throw $e;
        }
        return $html;
    }

    public function closeOffcanvas(): self
    {
        return $this->add(self::CLOSE_OFFCANVAS);
    }

    // Web Components actions

    /**
     * @param string $selector
     * @param string $method
     * @param mixed ...$methodParams
     * @return self
     */
    public function callWebComponent(string $selector, string $method, ...$methodParams): self
    {
        return $this->add(self::CALL_WEBCOMPONENT, [
            'selector' => $selector,
            'method' => $method,
            'method_params' => $methodParams
        ]);
    }

    // utils

    private function toSelector(?string $ids): string
    {
        if (null === $ids) {
            return 'table';
        }

        $selectors = array_map(
            fn ($id) => 'table#' . trim($id),
            explode(' ', $ids)
        );

        return implode(' ', $selectors);
    }

}
