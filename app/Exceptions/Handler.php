namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof HttpException && $e->getStatusCode() === 403
                || $e instanceof AccessDeniedHttpException
                || $e instanceof AuthorizationException
            ) {
                return Inertia::render('Error/Forbidden', [
                    'status' => 403,
                    'message' => $e->getMessage() ?: 'You do not have permission to access this resource.'
                ])->toResponse($request);
            }
        });
    }
}