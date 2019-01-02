<?php
namespace api\components\LongPolling\customers;

use common\components\enum\PushesActions;
use common\models\CarpoolPoint;

class CarpoolArrivedPush extends CarpoolPointPush
{
    protected $action = PushesActions::CARPOOL_POINT_ARRIVED;
}