Yii2-timeTracker
==========
Time tracker service, sync Microsoft Outlook and Tsheets APIs
---------------------------------

Enable migrations in console config:
---------------------------------
```
'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'app\migrations', // Common migrations for the whole application
                'app\modules\timeTracker\migrations', // Migrations for the specific project's module
            ],
        ],
    ],
```

Configuration:
---------------------------------

```
    'modules' => [
        'timeTracker' => [
            'class' => \app\modules\timeTracker\Module::class,
        ],
    ...
    ]
```


Console Configuration:
---------------------------------

```
    'bootstrap' => ['timeTracker'],

```



