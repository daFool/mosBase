#!/bin/bash

createuser mosbase
createdb -O mosbase mosbase
for i in tables/taulu_pohjat.sql tables/taulu_log.sql tables/taulu_testi.sql; do
    psql mosbase -f $i
    j=${i#*_}
    j=${j%\.sql}
    echo $j;
    psql mosbase <<sql
    grant all on $j to mosbase;
sql
done;
psql mosbase <<foo
    alter user mosbase with password 'mosbase';
    grant all on log_chain to mosbase;
    grant all on log_id_seq to mosbase;
    grant all on testi_id_seq to mosbase;
foo

