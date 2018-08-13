drop table if exists pohjat;
create table pohjat (
        muokattu        timestamp with time zone,
        muokkaaja       varchar(255),
        luotu           timestamp with time zone default now(),
        luoja           varchar(255)
        );
        
comment on table pohjat is 'Pohjataulu - ei tarkoitettu käytettäväksi, vaan osaksi kaikkia muita';
comment on column pohjat.muokattu is 'Koska tätä riviä on viimeksi muokattu';
comment on column pohjat.muokkaaja is 'Kuka tätä riviä on viimeksi muokannut';
comment on column pohjat.luotu is 'Koska tämä rivi on luotu';
comment on column pohjat.luoja is 'Kuka tämän rivin on luonut';     