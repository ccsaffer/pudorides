<?php
namespace api\components;

use yii\filters\auth\AuthMethod;
use yii\web\IdentityInterface;
use yii\web\Request;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use yii\web\User;

class HOPRestAuth extends AuthMethod
{

    /**
     * Authenticates the current user.
     * @param User $user
     * @param Request $request
     * @param Response $response
     * @return IdentityInterface the authenticated user identity. If authentication information is not provided, null will be returned.
     * @throws UnauthorizedHttpException if authentication information is provided but is invalid.
     */
    public function authenticate($user, $request, $response)
    {
        $this->loadGET($request, $id, $authKey);
        if (empty($id)) {
            $this->loadHeader($request, $id, $authKey);
        }

        if (empty($id)) {
            return null;
        }

        /** @var Callable $function Version grater than 5.4 */
        $function = [\Yii::$app->getUser()->identityClass, 'findIdentity'];

        /** @var IdentityInterface $identity */
        $identity = $function($id);

        if (!$identity) {
            throw new UnauthorizedHttpException('User not found');
        }

        //Временно комментируем
        if (!$identity->validateAuthKey($authKey)) {
            throw new UnauthorizedHttpException('Auth key bad', -1);
        }

        $user->login($identity);

        return $identity;
    }

    private function loadGET(Request $request, &$id, &$authKey)
    {
        $id      = trim($request->get('id'));
        $authKey = trim($request->get('auth_key'));
    }

    private function loadHeader(Request $request, &$id, &$authKey)
    {
        $id      = null;
        $authKey = null;
        $header  = $request->getHeaders()->get('Authorization');
        if (is_null($header)) {
            return;
        }

        $values = preg_split('/\s+/', $header);
        if ((sizeof($values) != 3) || ($values[0] != 'HOP')) {
            return;
        }

        $id      = $values[1];
        $authKey = $values[2];
    }
}