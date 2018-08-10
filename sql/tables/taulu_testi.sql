drop table if exists testi;
create table testi (
        id      serial primary key,
        intti   bigint,
        merkkijono      varchar(255),
        pvm     date,
        aika    time with time zone,
        aikaleima       timestamp with time zone,
        kommentti       text,
        
        like pohjat including ALL);
        