begin;

create table if not exists public.sql_mig_probe (
    id integer generated always as identity primary key,
    name text not null
);

commit;
