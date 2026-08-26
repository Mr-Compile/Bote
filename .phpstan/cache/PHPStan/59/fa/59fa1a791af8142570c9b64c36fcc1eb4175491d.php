<?php declare(strict_types = 1);

// odsl-C:\wamp64\www\Bote\tests
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    'C:\\wamp64\\www\\Bote\\tests\\bootstrap.php' => 
    array (
      0 => 'f93207d3454546eb2e6b82ef990a6794d439698d',
      1 => 
      array (
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Support\\FakeDropoffRepository.php' => 
    array (
      0 => '6ddd925aa823c31dc9eab9858cf5c30eb1343241',
      1 => 
      array (
        0 => 'tests\\support\\fakedropoffrepository',
      ),
      2 => 
      array (
        0 => 'tests\\support\\findbyid',
        1 => 'tests\\support\\save',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Support\\FakePickupRepository.php' => 
    array (
      0 => 'e34c8e18f15bd5fbfd35231a64cbc811a530e2f2',
      1 => 
      array (
        0 => 'tests\\support\\fakepickuprepository',
      ),
      2 => 
      array (
        0 => 'tests\\support\\findbyid',
        1 => 'tests\\support\\save',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Support\\FakePointsRepository.php' => 
    array (
      0 => 'dfda297b2dac86bcb9c951e131a4a9ac25344f62',
      1 => 
      array (
        0 => 'tests\\support\\fakepointsrepository',
      ),
      2 => 
      array (
        0 => 'tests\\support\\award',
        1 => 'tests\\support\\balanceforuser',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Models\\DropoffTest.php' => 
    array (
      0 => '1bea5cac1389f3d793b086f8f3044dae005298fd',
      1 => 
      array (
        0 => 'tests\\unit\\models\\dropofftest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\test_from_array_creates_dropoff',
        1 => 'tests\\unit\\models\\test_verified_returns_new_dropoff',
        2 => 'tests\\unit\\models\\test_rejected_returns_new_dropoff',
        3 => 'tests\\unit\\models\\pendingdropoff',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Models\\PickupTest.php' => 
    array (
      0 => 'e756f1b4b5a05acd443f19e10ea373085ad3baf8',
      1 => 
      array (
        0 => 'tests\\unit\\models\\pickuptest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\test_from_array_creates_pickup',
        1 => 'tests\\unit\\models\\test_completed_returns_new_pickup_with_points',
        2 => 'tests\\unit\\models\\test_completed_throws_when_already_completed',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Models\\RecyclableTypeTest.php' => 
    array (
      0 => '746fc3280883b6340e9a3056cc6a7b3251aee764',
      1 => 
      array (
        0 => 'tests\\unit\\models\\recyclabletypetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\test_from_array_creates_kg_type',
        1 => 'tests\\unit\\models\\test_from_array_creates_pcs_type',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Services\\DropoffServiceTest.php' => 
    array (
      0 => 'c88134a81d0f573e5aed54a108c82d6ff2bff3b5',
      1 => 
      array (
        0 => 'tests\\unit\\services\\dropoffservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\test_verify_awards_points_and_completes_dropoff',
        2 => 'tests\\unit\\services\\test_reject_does_not_award_points',
        3 => 'tests\\unit\\services\\test_verify_throws_when_not_pending',
        4 => 'tests\\unit\\services\\test_reject_throws_when_not_pending',
        5 => 'tests\\unit\\services\\creatependingdropoff',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Services\\PickupServiceTest.php' => 
    array (
      0 => '78e5a22fbfce8c604d0ce51e813a3042a42feafd',
      1 => 
      array (
        0 => 'tests\\unit\\services\\pickupservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\test_complete_kg_pickup_awards_points_and_updates_status',
        2 => 'tests\\unit\\services\\test_complete_pcs_pickup_uses_quantity',
        3 => 'tests\\unit\\services\\test_complete_throws_when_worker_not_assigned',
        4 => 'tests\\unit\\services\\test_complete_throws_when_already_completed',
        5 => 'tests\\unit\\services\\test_complete_throws_when_missing_quantity_for_pcs',
        6 => 'tests\\unit\\services\\createscheduledpickup',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Services\\PointsServiceTest.php' => 
    array (
      0 => '393265bfff32d626da956638b76903d4399d6e42',
      1 => 
      array (
        0 => 'tests\\unit\\services\\pointsservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\test_calculate_kg_material_uses_weight',
        2 => 'tests\\unit\\services\\test_calculate_pcs_material_uses_quantity',
        3 => 'tests\\unit\\services\\test_calculate_rejects_negative_weight_for_kg',
        4 => 'tests\\unit\\services\\test_calculate_rejects_missing_quantity_for_pcs',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Support\\FakeConnection.php' => 
    array (
      0 => '4190bab5a69a46d54cb05b9a32718faac79ddb11',
      1 => 
      array (
        0 => 'tests\\support\\fakeconnection',
      ),
      2 => 
      array (
        0 => 'tests\\support\\query',
        1 => 'tests\\support\\selectone',
        2 => 'tests\\support\\execute',
        3 => 'tests\\support\\insert',
        4 => 'tests\\support\\begintransaction',
        5 => 'tests\\support\\commit',
        6 => 'tests\\support\\rollback',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Core\\ContainerTest.php' => 
    array (
      0 => 'ea3f3d8f5097f2d17f0c9416225b6c7ccf9da90a',
      1 => 
      array (
        0 => 'tests\\unit\\core\\containertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\core\\test_container_wires_services_from_connection',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\tests\\Unit\\Services\\PickupServiceAdminTest.php' => 
    array (
      0 => '57eddccc7913ff50c691048805ecd1cb8afe60cc',
      1 => 
      array (
        0 => 'tests\\unit\\services\\pickupserviceadmintest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\test_complete_as_admin_uses_stored_quantity_for_pcs',
        2 => 'tests\\unit\\services\\test_complete_as_admin_does_not_check_worker',
        3 => 'tests\\unit\\services\\createscheduledpickup',
      ),
      3 => 
      array (
      ),
    ),
  ),
));