#!/bin/bash
export mosBase=`pwd`
export mosBaseIni=$mosBase/mosbase.ini
kohde="$mosBase/tests/unit/logTest.php"
cat $mosBase/tests/unit/logirivit_tpl.xml|sed 's+#PROJEKTI#+'$kohde'+g' > $mosBase/tests/unit/logirivit.xml
