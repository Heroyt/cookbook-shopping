<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mcp\ApproveMcpAuthorizationRequest;
use App\Mcp\Actions\AuthorizeMcpConnection;
use App\Mcp\McpConsentFamilyBinding;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Laravel\Passport\Http\Controllers\RetrievesAuthRequestFromSession;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

final class ApproveMcpAuthorizationController extends Controller
{
    use ConvertsPsrResponses;
    use HandlesOAuthErrors;
    use RetrievesAuthRequestFromSession;

    public function __construct(
        private readonly AuthorizationServer $server,
        private readonly AuthorizeMcpConnection $authorizeMcpConnection,
        private readonly McpConsentFamilyBinding $consentFamilyBinding,
    ) {}

    public function __invoke(
        ApproveMcpAuthorizationRequest $request,
        ResponseInterface $psrResponse,
    ): Response {
        $authRequest = $this->getAuthRequestFromSession($request);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return DB::transaction(function () use ($authRequest, $psrResponse, $request, $user): Response {
            $clientId = $authRequest->getClient()->getIdentifier();
            $family = $this->consentFamilyBinding->resolve(
                $user,
                $clientId,
                $request->string('auth_token')->toString(),
                $request->familyBinding(),
            );
            $this->authorizeMcpConnection->handle(
                $user,
                $family,
                $clientId,
                $request->abilities(),
            );

            $authRequest->setAuthorizationApproved(true);

            return $this->withErrorHandling(fn () => $this->convertResponse(
                $this->server->completeAuthorizationRequest($authRequest, $psrResponse),
            ), $authRequest->getGrantTypeId() === 'implicit');
        }, 3);
    }
}
