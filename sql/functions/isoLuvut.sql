drop function if exists isoLuku(bigint);
drop function if exists isoLukuLiite(bigint);
drop function if exists stringiLuku(bigint);
drop function if exists isoSkaala(bigint, char(1));

create function isoSkaala(luku bigint, liite char(2)) returns numeric as '
    declare i int;
    declare k numeric;
    declare liitteet varchar[];
 
    begin
       liitteet := ARRAY[''B'', ''kB'', ''MB'', ''GB'', ''TB'', ''PB'', ''YB''];

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
        k := round((luku::numeric*100 / pow(1000,i))) / 100;
        return k;
    end;        
'
LANGUAGE plpgsql;

create function isoLuku(luku bigint) returns numeric as '
    declare i int;
    declare k numeric;
        
    begin
        if luku is null or luku=0 then
            i:=0;
        else
            i := floor(log(1000,luku));
        end if;
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
        if luku is null or luku=0 then
            i:=0;
        else
            i := floor(log(1000,luku));
        end if;
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