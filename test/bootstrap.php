<?php

\chdir(\dirname(__DIR__));
\ini_set('memory_limit', '512M');
\error_reporting(E_ALL);

require './vendor/antecedent/patchwork/Patchwork.php';
require './vendor/autoload.php';

require './src/Rah/Sitemap/ControllerInterface.php';
require './src/Rah/Sitemap/RecordInterface.php';
require './src/Rah/Sitemap/Record/AbstractRecord.php';
require './src/Rah/Sitemap/Record/ArticleRecord.php';
require './src/Rah/Sitemap/Record/CategoryRecord.php';
require './src/Rah/Sitemap/Record/SectionRecord.php';
require './src/Rah/Sitemap/Record/SiteRecord.php';
require './src/Rah/Sitemap/RecordPool.php';
require './src/Rah/Sitemap/Url.php';
require './src/Rah/Sitemap/Controller/IndexController.php';
require './src/Rah/Sitemap/Controller/RobotsController.php';
require './src/Rah/Sitemap/Controller/SitemapController.php';
require './src/Rah/Sitemap/Router.php';
require './src/Rah/Sitemap.php';
