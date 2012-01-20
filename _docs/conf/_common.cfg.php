<?php

// Включен ли е дебъга? Той ще бъде включен и когато
// текущия потребител има роля 'tester'
# DEFINE('EF_DEBUG', TRUE);

// Списък, разделен със запетайки на IP-та и/или localhost
// Когато заявката идва от такова IP и EF_DEBUG не зададено
// се приема, че системата е в режим DEBUG
 # DEFINE('EF_DEBUG_HOSTS', 'localhost,127.0.0.1');

// Секретен ключ използван за кодиране в рамките на системата
// Той трябва да е различен, за различните инсталации на системата
// Моля сменето стойността, ако правите нова инсталация.
// След като веднъж е установен, този параметър не трябва да се променя
defIfNot('EF_SALT', '');

// Името на приложението, ако то твърдо зададено                         
 # DEFINE('EF_APP_NAME', '' );

// Името на контролера, ако той е твърдо зададен                            
 # DEFINE( EF_CTR_NAME, '' );

// Името на екшъна, ако той е твърдо зададен                            
 # DEFINE( EF_ACT_NAME, '' );

// Обща коренна директория на системата, ако има такава
// Тя съдържа по подразбиране папките на приложенията, 
// EF, CONF, TEMP, UPLOADS и VENDORS
// Възможно е за всяка папка да се посочи конкретен път
// По подразбиране е "[папката на този файл]/ef_root"
 # DEFINE('EF_ROOT_PATH', 'c:/xampp/ef');

// Директорията с кода на фреймуърка
// По подразбиране е 'EF_ROOT/ef'
 # DEFINE( EF_EF_PATH, '' );

// Директорията с конфигурационните файлове за приложенията
// По подразбиране е 'EF_ROOT_PATH/conf'
 # DEFINE( EF_CONF_PATH, '' );
 
// Директорията с външни пакети
// По подразбиране е 'EF_ROOT/vendors'
 # DEFINE( EF_VENDORS_PATH, '' );

// Директорията с временни файлове
// По подразбиране е 'EF_ROOT/temp'
 # DEFINE( EF_TEMP_PATH, '' );

// Директорията с качените и генерираните файлове
// По подразбиране е 'EF_ROOT/uploads'
 # DEFINE( EF_UPLOADS_PATH, '' );

// Името на приложението по подразбиране 
defIfNot('EF_DEFAULT_APP_NAME', 'ef');

// Името на контролера по подразбиране 
defIfNot('EF_DEFAULT_CTR_NAME', 'Index');

// Името на екшъна по подразбиране 
defIfNot('EF_DEFAULT_ACT_NAME', 'default');