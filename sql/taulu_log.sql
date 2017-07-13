drop table if exists log;
create table log (
        id      serial primary key,
        koska   timestamp with time zone default now(),
        kuka    varchar(255) default 'system',
        viesti  text,
        tiedosto        varchar(516),
        tarkenne        varchar(516),
        rivi    int,
        luokka  varchar(255),
        mista   inet,
        selain  varchar(255)
        );
comment on table log is 'Järjestelmän logitaulu.';
comment on column log.id is 'Login juokseva järjestysnumero / avain';
comment on column log.koska is 'Koska rivi on tuotu kantaan / jotakin on tapahtunut';
comment on column log.kuka is 'Kuka/mikä on aiheuttanut tapahtuman';
comment on column log.viesti is 'Mistä tapahtumassa on kyse?';
comment on column log.tiedosto is 'Mikä lähdetiedosto on generoinut viestin';
comment on column log.tarkenne is 'Mikä luokka/objekti/funktio tms on generoinut viestin';
comment on column log.rivi is 'Mikä lähdetiedoston rivi on generoinut viestin';
comment on column log.luokka is 'Mihin luokkaan: DEBUG/AUDIT/ERROR tms tapahtuma kuuluu';
comment on column log.mista is 'Mistä ip-osoitteesta viesti on peräisin?';
comment on column log.selain is 'Mikä selainstringi on aiheuttanut viestin?';

        
        
        