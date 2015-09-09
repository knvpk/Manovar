@servers(['application_server' => 'accel-application'])

<?php
$repo = 'git@github.com:pavankumarkatakam/Manovar.git';
$release_dir = '/var/www/manovar_releases';
$app_dir = '/var/www/manovar';
$release = 'release_' . date('YmdHis');
?>

@macro('deploy', ['on' => 'application_server'])
fetch_repo
run_composer
datatbase_init
update_permissions
update_symlinks
@endmacro

@task('fetch_repo')
[ -d {{ $release_dir }} ] || mkdir {{ $release_dir }};
cd {{ $release_dir }};
git clone -b master {{ $repo }} {{ $release }};
@endtask

@task('run_composer')
cd {{ $release_dir }}/{{ $release }}
composer install --prefer-dist --no-scripts;
php artisan clear-compiled --env=production;
php artisan optimize --env=production;
@endtask

@task('datatbase_init')
cd {{ $release_dir }}/{{ $release }}
php artisan migrate --force --seed --env=production
@endtask

@task('update_permissions')
cd {{ $release_dir }};
chgrp -R www-data {{ $release }};
chmod -R ug+rwx {{ $release }};
@endtask

@task('update_symlinks')
ln -nfs {{ $release_dir }}/{{ $release }} {{ $app_dir }};
chgrp -h www-data {{ $app_dir }};

cd {{ $release_dir }}/{{ $release }};
ln -nfs ../../.env .env;
chgrp -h www-data .env;


rm -r {{ $release_dir }}/{{ $release }}/storage/logs;
cd {{ $release_dir }}/{{ $release }}/storage;
ln -nfs ../../../logs logs;
chgrp -h www-data logs;

sudo service php5-fpm reload;
@endtask