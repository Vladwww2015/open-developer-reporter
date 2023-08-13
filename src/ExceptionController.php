<?php

namespace OpenDeveloper\Developer\Reporter;

use Illuminate\Http\JsonResponse;
use OpenDeveloper\Developer\Controllers\ModelForm;
use OpenDeveloper\Developer\Facades\Developer;
use OpenDeveloper\Developer\Grid;
use OpenDeveloper\Developer\Layout\Content;
use OpenDeveloper\Developer\Reporter\Actions\ViewReport;
use OpenDeveloper\Developer\Reporter\Tracer\Parser;

class ExceptionController
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(): Content
    {
        return Developer::content(function (Content $content) {
            $content->header('Exception');
            $content->description('Exception list..');

            $content->body($this->grid());
        });
    }

    public function grid(): Grid
    {
        return Developer::grid(ExceptionModel::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->id('ID')->sortable();

            $grid->type()->display(function ($type) {
                $path = explode('\\', $type);

                return array_pop($path);
            });

            $grid->code();
            $grid->message()->style('width:400px')->display(function ($message) {
                if (empty($message)) {
                    return '';
                }

                return "<code>$message</code>";
            });

            $grid->request()->display(function () {
                $color = ExceptionModel::$methodColor[$this->method];

                return sprintf(
                    '<span class="badge bg-%s me-2">%s</span><code>%s</code>',
                    $color,
                    $this->method,
                    $this->path
                );
            });

            $grid->input()->display(function ($input) {
                $input = json_decode($input, true);

                if (empty($input)) {
                    return '';
                }

                return '<pre>'.json_encode($input, JSON_PRETTY_PRINT).'</pre>';
            });

            $grid->created_at();

            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->like('type');
                $filter->like('message');
                $filter->between('created_at')->datetime();
            });

            $grid->disableCreation();

            $grid->actions(function (Grid\Displayers\Actions\Actions $actions) {
                $actions->disableEdit();
                //$actions->pre(new ViewReport()); // if you want an extra button
            });
        });
    }

    public function show($id)
    {
        return Developer::content(function (Content $content) use ($id) {
            $content->header('Exception');
            $content->description('Exception detail.');

            Developer::script('Prism.highlightAll();');

            $exception = ExceptionModel::findOrFail($id);
            $trace = "#0 {$exception->file}({$exception->line})\n";
            $frames = (new Parser($trace.$exception->trace))->parse();
            $cookies = json_decode($exception->cookies, true);
            $headers = json_decode($exception->headers, true);

            array_pop($frames);

            $view = view('open-developer-reporter::exception', compact('exception', 'frames', 'cookies', 'headers'));

            $content->body($view);
        });
    }

    public function destroy($id): JsonResponse
    {
        $ids = explode(',', $id);

        if (ExceptionModel::query()->whereIn('id', $ids)->delete()) {
            return response()->json([
                'status'  => true,
                'message' => trans('developer.delete_succeeded'),
            ]);
        } else {
            return response()->json([
                'status'  => false,
                'message' => trans('developer.delete_failed'),
            ]);
        }
    }
}
