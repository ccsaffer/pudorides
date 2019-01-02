<?php
namespace api\components;

use api\components\LongPolling\customers\CarpoolApproachPush;
use api\components\LongPolling\customers\CarpoolArrivedPush;
use api\components\LongPolling\customers\CarpoolCompletedPush;
use api\components\LongPolling\customers\CarpoolRevokedPush;
use api\components\LongPolling\customers\CarpoolStartedPush;
use api\components\LongPolling\customers\CarpoolTakenPush;
use api\components\LongPolling\drivers\UpdateRegionPush;
use common\components\enum\PushesActions;
use common\components\pushes\CarpoolCompleteNotice;
use common\components\pushes\CarpoolNotice;
use common\components\pushes\CarpoolPointNotice;
use common\models\Carpool;
use common\models\CarpoolPassenger;
use common\models\CarpoolPoint;
use common\models\Driver;
use common\models\PublicSettings;
use common\models\User;
use yii\helpers\ArrayHelper;

class CarpoolPushesHelper
{
    public static function acceptPushes(Carpool $carpool, Driver $driver)
    {
        $notice = new CarpoolNotice();

        $notice->event   = PushesActions::CARPOOL_TAKEN;
        $notice->carpool = $carpool;
        $notice->car     = $driver->currentCar;
        $notice->message = PublicSettings::getByKey(PublicSettings::CARPOOL_TAKEN_MESSAGE);
        $notice->execMany($carpool->users);

        (new CarpoolTakenPush($carpool->users))
            ->setCarpool($carpool)
            ->setDriver($driver)
            ->push();

        (new UpdateRegionPush(ArrayHelper::getColumn($carpool->regions, 'id')))->push();
    }

    public static function approachPushes(Carpool $carpool, Driver $driver, CarpoolPoint $point)
    {
        $notice = new CarpoolPointNotice();

        $notice->event   = PushesActions::CARPOOL_POINT_APPROACH;
        $notice->carpool = $carpool;
        $notice->car     = $driver->currentCar;
        $notice->point   = $point;
        $notice->message = PublicSettings::getByKey(PublicSettings::CARPOOL_APPROACH_MESSAGE);
        $notice->execMany($point->passengers);

        (new CarpoolApproachPush($point->passengers))
            ->setCarpool($carpool)
            ->setPoint($point)
            ->setDriver($driver)
            ->push();
    }

    public static function arrivedPushes(Carpool $carpool, Driver $driver, CarpoolPoint $point)
    {
        $notice = new CarpoolPointNotice();

        $notice->event   = PushesActions::CARPOOL_POINT_ARRIVED;
        $notice->carpool = $carpool;
        $notice->car     = $driver->currentCar;
        $notice->point   = $point;
        $notice->message = PublicSettings::getByKey(PublicSettings::CARPOOL_ARRIVED_MESSAGE);
        $notice->execMany($point->passengers);

        (new CarpoolArrivedPush($point->passengers))
            ->setCarpool($carpool)
            ->setPoint($point)
            ->setDriver($driver)
            ->push();
    }

//    public static function startedPushes(Carpool $carpool, Driver $driver, CarpoolPoint $point)
//    {
//        $notice = new CarpoolPointNotice();
//
//        $notice->event   = PushesActions::CARPOOL_STARTED;
//        $notice->carpool = $carpool;
//        $notice->car     = $driver->currentCar;
//        $notice->point   = $point;
//        $notice->message = PublicSettings::getByKey(PublicSettings::CARPOOL_STARTED_MESSAGE);
//        $notice->execMany($carpool->users);
//
//        (new CarpoolStartedPush($carpool->users))
//            ->setCarpool($carpool)
//            ->setPoint($point)
//            ->setDriver($driver)
//            ->push();
//    }

    public static function completedPushes(Carpool $carpool, Driver $driver, CarpoolPoint $point)
    {
        /** @var CarpoolPassenger[] $passengers */
        $passengers = $carpool->getPassengers()->where(['user_id' => ArrayHelper::getColumn($point->passengers, 'id')])->all();
        /** @var User[] $users */
        $users = ArrayHelper::index($point->passengers, 'id');

        foreach ($passengers as $passenger) {
            $notice          = new CarpoolCompleteNotice();
            $notice->event   = PushesActions::CARPOOL_COMPLETED;
            $notice->carpool = $carpool;
            $notice->car     = $driver->currentCar;
            $notice->point   = $point;
            $notice->cost    = $passenger->getRealCost();
            $notice->miles   = ApiHelper::milesFromMeters($passenger->distance_meters);
            $notice->seconds = $passenger->ride_time;
            $notice->message = PublicSettings::getByKey(PublicSettings::CARPOOL_COMPLETED_MESSAGE);
            $notice->exec($passenger->user_id);

            if (isset($users[$passenger->user_id])) {
                (new CarpoolCompletedPush($users[$passenger->user_id]))
                    ->setCarpool($carpool)
                    ->setPassenger($passenger)
                    ->setDriver($driver)
                    ->setPoint($point)
                    ->push();
            }
        }
    }

    public static function revokePushes(Carpool $carpool, Driver $driver)
    {
        $notice = new CarpoolNotice();

        $notice->event   = PushesActions::CARPOOL_REVOKED;
        $notice->carpool = $carpool;
        $notice->car     = $driver->currentCar;
        $notice->message = PublicSettings::getByKey(PublicSettings::CARPOOL_REVOKED_MESSAGE);
        $notice->execMany($carpool->users);

        (new CarpoolRevokedPush($carpool->users))
            ->setCarpool($carpool)
            ->setDriver($driver)
            ->push();
    }
}