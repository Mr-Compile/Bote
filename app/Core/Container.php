<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\MysqliDropoffRepository;
use App\Repositories\MysqliPickupRepository;
use App\Repositories\MysqliPointsRepository;
use App\Repositories\MysqliRecyclableTypeRepository;
use App\Services\DropoffRequestService;
use App\Services\DropoffService;
use App\Services\PickupRequestService;
use App\Services\PickupService;
use App\Services\PointsService;

final class Container
{
    public readonly ConnectionInterface $database;
    public readonly MysqliPickupRepository $pickupRepository;
    public readonly MysqliDropoffRepository $dropoffRepository;
    public readonly MysqliPointsRepository $pointsRepository;
    public readonly MysqliRecyclableTypeRepository $recyclableTypeRepository;
    public readonly PointsService $pointsService;
    public readonly PickupService $pickupService;
    public readonly DropoffService $dropoffService;
    public readonly PickupRequestService $pickupRequestService;
    public readonly DropoffRequestService $dropoffRequestService;

    public function __construct(ConnectionInterface $database)
    {
        $this->database = $database;

        $this->pointsRepository = new MysqliPointsRepository($database);
        $this->pickupRepository = new MysqliPickupRepository($database);
        $this->dropoffRepository = new MysqliDropoffRepository($database);
        $this->recyclableTypeRepository = new MysqliRecyclableTypeRepository($database);
        $this->pointsService = new PointsService();

        $this->pickupService = new PickupService(
            db: $database,
            pickups: $this->pickupRepository,
            points: $this->pointsRepository,
            calculator: $this->pointsService,
        );

        $this->dropoffService = new DropoffService(
            db: $database,
            dropoffs: $this->dropoffRepository,
            points: $this->pointsRepository,
        );

        $this->pickupRequestService = new PickupRequestService(
            pickups: $this->pickupRepository,
            types: $this->recyclableTypeRepository,
        );

        $this->dropoffRequestService = new DropoffRequestService(
            dropoffs: $this->dropoffRepository,
            types: $this->recyclableTypeRepository,
            calculator: $this->pointsService,
        );
    }
}
