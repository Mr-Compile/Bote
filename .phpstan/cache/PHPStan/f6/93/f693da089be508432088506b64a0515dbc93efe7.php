<?php declare(strict_types = 1);

// odsl-C:\wamp64\www\Bote\app
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    'C:\\wamp64\\www\\Bote\\app\\Core\\ConnectionInterface.php' => 
    array (
      0 => '065a75d3c71e1ff943e92151a4562caac1219a16',
      1 => 
      array (
        0 => 'app\\core\\connectioninterface',
      ),
      2 => 
      array (
        0 => 'app\\core\\query',
        1 => 'app\\core\\selectone',
        2 => 'app\\core\\execute',
        3 => 'app\\core\\insert',
        4 => 'app\\core\\begintransaction',
        5 => 'app\\core\\commit',
        6 => 'app\\core\\rollback',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Core\\Database.php' => 
    array (
      0 => '96df19566b68d2547ed8fd80eae6b5d7dd496922',
      1 => 
      array (
        0 => 'app\\core\\database',
      ),
      2 => 
      array (
        0 => 'app\\core\\__construct',
        1 => 'app\\core\\query',
        2 => 'app\\core\\selectone',
        3 => 'app\\core\\execute',
        4 => 'app\\core\\insert',
        5 => 'app\\core\\begintransaction',
        6 => 'app\\core\\commit',
        7 => 'app\\core\\rollback',
        8 => 'app\\core\\prepare',
        9 => 'app\\core\\bind',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Enums\\DropoffStatus.php' => 
    array (
      0 => 'c772f76dc12a1929d4d80769f9c101391f688bc4',
      1 => 
      array (
        0 => 'app\\enums\\dropoffstatus',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Enums\\PickupStatus.php' => 
    array (
      0 => 'f98fa656543a8ac1ba08a68ffedbc92118ee6e77',
      1 => 
      array (
        0 => 'app\\enums\\pickupstatus',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Enums\\TransactionType.php' => 
    array (
      0 => '5b72db98423ff77cee5e13ee58a6f24cf476d399',
      1 => 
      array (
        0 => 'app\\enums\\transactiontype',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Enums\\Unit.php' => 
    array (
      0 => '16aa0262413b18a27ae7091c5d2cedf8cb7ba4ed',
      1 => 
      array (
        0 => 'app\\enums\\unit',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Models\\Dropoff.php' => 
    array (
      0 => '76d6f4df365b72f681f121017ca818d74e94078e',
      1 => 
      array (
        0 => 'app\\models\\dropoff',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\fromarray',
        2 => 'app\\models\\verified',
        3 => 'app\\models\\rejected',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Models\\Pickup.php' => 
    array (
      0 => '3e7f2648134f4558ad2a841ca5133d42bf4191fa',
      1 => 
      array (
        0 => 'app\\models\\pickup',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\fromarray',
        2 => 'app\\models\\assigntoworker',
        3 => 'app\\models\\completed',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Models\\RecyclableType.php' => 
    array (
      0 => '6adbbc530dde83364ba28056f759e5cd7b73dee6',
      1 => 
      array (
        0 => 'app\\models\\recyclabletype',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\fromarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\DropoffRepositoryInterface.php' => 
    array (
      0 => 'e8befa2ad9d18da000f308c8153ef021f887ac4c',
      1 => 
      array (
        0 => 'app\\repositories\\dropoffrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\findbyid',
        1 => 'app\\repositories\\save',
        2 => 'app\\repositories\\create',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\MysqliDropoffRepository.php' => 
    array (
      0 => '234cf86ec2ab4eebb53f32e43d3adcaf2d1e91d3',
      1 => 
      array (
        0 => 'app\\repositories\\mysqlidropoffrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\findbyid',
        2 => 'app\\repositories\\save',
        3 => 'app\\repositories\\create',
        4 => 'app\\repositories\\hydrate',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\MysqliPickupRepository.php' => 
    array (
      0 => '945e19d6f644d222f401898cd1a20a7b82525bc4',
      1 => 
      array (
        0 => 'app\\repositories\\mysqlipickuprepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\findbyid',
        2 => 'app\\repositories\\save',
        3 => 'app\\repositories\\assign',
        4 => 'app\\repositories\\create',
        5 => 'app\\repositories\\hydrate',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\MysqliPointsRepository.php' => 
    array (
      0 => '8fccbce842376f51e5542980f033a630e3c96711',
      1 => 
      array (
        0 => 'app\\repositories\\mysqlipointsrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\award',
        2 => 'app\\repositories\\balanceforuser',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\PickupRepositoryInterface.php' => 
    array (
      0 => '8774cc0faf49d04f0b7de8e6dd572841b9b5f673',
      1 => 
      array (
        0 => 'app\\repositories\\pickuprepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\findbyid',
        1 => 'app\\repositories\\save',
        2 => 'app\\repositories\\assign',
        3 => 'app\\repositories\\create',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\PointsRepositoryInterface.php' => 
    array (
      0 => '3628f67b4ec86ac91f3a57a71ec3e7c0de5ea35d',
      1 => 
      array (
        0 => 'app\\repositories\\pointsrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\award',
        1 => 'app\\repositories\\balanceforuser',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Services\\DropoffService.php' => 
    array (
      0 => '600092d39093bb6e13fc58ae913a8b8ad6af309b',
      1 => 
      array (
        0 => 'app\\services\\dropoffservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\verify',
        2 => 'app\\services\\reject',
        3 => 'app\\services\\findorfail',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Services\\PickupService.php' => 
    array (
      0 => '0d85d9f7ee1777a7721cee59faf44dbed103efcd',
      1 => 
      array (
        0 => 'app\\services\\pickupservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\complete',
        2 => 'app\\services\\completeasadmin',
        3 => 'app\\services\\completefor',
        4 => 'app\\services\\guardinputagainstunit',
        5 => 'app\\services\\effectivequantity',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Services\\PointsService.php' => 
    array (
      0 => '6e55912541a84eb645445f2157047ed8a1255781',
      1 => 
      array (
        0 => 'app\\services\\pointsservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\calculate',
        1 => 'app\\services\\calculatebyweight',
        2 => 'app\\services\\calculatebyquantity',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Core\\TypeCaster.php' => 
    array (
      0 => '9c5526c7a070b9382dac744f1f8e6cab298bb486',
      1 => 
      array (
        0 => 'app\\core\\typecaster',
      ),
      2 => 
      array (
        0 => 'app\\core\\int',
        1 => 'app\\core\\nullableint',
        2 => 'app\\core\\float',
        3 => 'app\\core\\nullablefloat',
        4 => 'app\\core\\string',
        5 => 'app\\core\\nullablestring',
        6 => 'app\\core\\enum',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Core\\Container.php' => 
    array (
      0 => 'f5ccc2adfc8bfac7a28b68ba116743f2000ecadf',
      1 => 
      array (
        0 => 'app\\core\\container',
      ),
      2 => 
      array (
        0 => 'app\\core\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\MysqliRecyclableTypeRepository.php' => 
    array (
      0 => '266765dde613907ca86e44d82458e65107525585',
      1 => 
      array (
        0 => 'app\\repositories\\mysqlirecyclabletyperepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\findbyid',
        2 => 'app\\repositories\\all',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Repositories\\RecyclableTypeRepositoryInterface.php' => 
    array (
      0 => '798fe7f6e3ad112d1bb61995ffd635bdf73b3866',
      1 => 
      array (
        0 => 'app\\repositories\\recyclabletyperepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\findbyid',
        1 => 'app\\repositories\\all',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Services\\DropoffRequestService.php' => 
    array (
      0 => '8772c867b34b881a970686a238e5e3a5680908fc',
      1 => 
      array (
        0 => 'app\\services\\dropoffrequestservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\create',
        2 => 'app\\services\\findtypeorfail',
        3 => 'app\\services\\validatematerial',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Services\\PickupRequestService.php' => 
    array (
      0 => '8161bde8117ab7b8da043f169ea5f68496d65b1d',
      1 => 
      array (
        0 => 'app\\services\\pickuprequestservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\create',
        2 => 'app\\services\\assigntoworker',
        3 => 'app\\services\\findtypeorfail',
        4 => 'app\\services\\validatematerial',
        5 => 'app\\services\\validatepickuptype',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\wamp64\\www\\Bote\\app\\Core\\Csrf.php' => 
    array (
      0 => 'de63ff0fef043e1b93bbb9c398e251d160455960',
      1 => 
      array (
        0 => 'app\\core\\csrf',
      ),
      2 => 
      array (
        0 => 'app\\core\\ensuresessionstarted',
        1 => 'app\\core\\token',
        2 => 'app\\core\\field',
        3 => 'app\\core\\validate',
        4 => 'app\\core\\submittedtoken',
        5 => 'app\\core\\validaterequest',
        6 => 'app\\core\\reset',
      ),
      3 => 
      array (
      ),
    ),
  ),
));