drop function if exists isoLuku(bigint);
drop function if exists isoLukuLiite(bigint);
drop function if exists stringiLuku(bigint);

create function isoLuku(luku bigint) returns numeric as '
    declare i int;
    declare k numeric;
        
    begin
        i := floor(log(1000,luku));
        k := round((luku::numeric*100 / pow(1000,i))) / 100;
        return k;
    end;

'
LANGUAGE plpgsql;

create function isoLukuLiite(luku bigint) returns varchar as '
    declare i int;
    declare k numeric;
    declare tulos varchar(255);
    declare l varchar(2);
    declare alku varchar(255);
    declare liitteet varchar[];

    begin
        liitteet := ARRAY[''B'', ''kB'', ''MB'', ''GB'', ''TB'', ''PB'', ''YB''];
        i := floor(log(1000,luku));
        l := liitteet[i+1];
        return l;
    end;
' LANGUAGE plpgsql;

create function stringiLuku(luku bigint) returns varchar as '
    declare i int;
    declare k numeric;
    declare l varchar(2);
    declare alku varchar(255);
    
    begin
        select into k isoLuku(luku);
        select into l isoLukuLiite(luku);
        
        alku := to_char(k,''999.99'');
        return concat(alku, l);
    end;
'
LANGUAGE plpgsql;