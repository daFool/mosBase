drop function if exists iecLuku(bigint);
drop function if exists iecLukuLiite(bigint);
drop function if exists stringiLevyLuku(bigint);
drop function if exists iecSkaala(bigint, char(1));

create function iecSkaala(luku bigint, liite char(3)) returns numeric as '
    declare i int;
    declare k numeric;
    declare liitteet varchar[];
 
    begin
       liitteet := ARRAY[''B'', ''kiB'', ''MiB'', ''GiB'', ''TiB'', ''PiB'', ''YiB''];

        i := 1;
        loop
            if liitteet[i]=liite then
                exit;
            end if;
            i := i+1;
            if i > 7 then
                exit;
            end if;
        end loop;
        i := i-1;
        k := round((luku::numeric*100 / pow(1024,i))) / 100;
        return k;
    end;        
'
LANGUAGE plpgsql;

create function iecLuku(luku bigint) returns numeric as '
    declare i int;
    declare k numeric;
        
    begin
        if luku is null or luku=0 then
            i:=0;
        else
            i := floor(log(1000,luku));
        end if;
        k := round((luku::numeric*100 / pow(1024,i))) / 100;
        return k;
    end;

'
LANGUAGE plpgsql;

create function iecLukuLiite(luku bigint) returns varchar as '
    declare i int;
    declare k numeric;
    declare tulos varchar(255);
    declare l varchar(3);
    declare alku varchar(255);
    declare liitteet varchar[];

    begin
        liitteet := ARRAY[''B'', ''kiB'', ''MiB'', ''GiB'', ''TiB'', ''PiB'', ''YiB''];
        if luku is null or luku=0 then
            i:=0;
        else
            i := floor(log(1024,luku));
        end if;
        l := liitteet[i+1];
        return l;
    end;
' LANGUAGE plpgsql;

create function stringiLevyLuku(luku bigint) returns varchar as '
    declare i int;
    declare k numeric;
    declare l varchar(3);
    declare alku varchar(255);
    
    begin
        select into k iecLuku(luku);
        select into l iecLukuLiite(luku);
        
        alku := to_char(k,''999.99'');
        return concat(alku, l);
    end;
'
LANGUAGE plpgsql;