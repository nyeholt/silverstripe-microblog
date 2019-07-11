<?php

namespace Symbiote\MicroBlog\Control;

use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\HTTPRequest;
use Symbiote\MicroBlog\Model\AuthenticationToken;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class RequestTokenAuthenticationMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $token = $request->getHeader('X-API-Key');

        if ($token) {
            $auth = AuthenticationToken::get()->filter('Token', $token)->first();
            if ($auth) {
                $user = $auth->Member();
                if ($user && $user->ID) {
                    Security::setCurrentUser($user);
                }
            }
        }

        return $delegate($request);
    }
}
