/*
    Licenses contain your secret/public keys for use in verifying private
    packages. If you do not have the corresponding secret key, you cannot
    decrypt the message from the blockchain and learn about a package's
    existence, let alone verify it.
*/
CREATE TABLE licenses (
    id INTEGER PRIMARY KEY ASC,
    secretkey TEXT,
    publickey TEXT,
    created TEXT,
    modified TEXT
);
/*
    Mirrors are for our packages themselves. The blockchain is handled with
    with notaries.
*/
CREATE TABLE mirrors (
    id INTEGER PRIMARY KEY ASC,
    url TEXT,
    created TEXT,
    modified TEXT
);
/*
    Notaries can be remote or local. Their public keys are pinned and they
    share information about the blocktree for independent verification.
    Notaries are considered trusted if they are added manually. There are 
    currently no automatic notary protocols, but this may change. See the
    documentation for more.
*/
CREATE TABLE notaries (
    id INTEGER PRIMARY KEY ASC,
    nickname TEXT,
    publickey TEXT,
    host TEXT,
    https INTEGER,
    port INTEGER,
    trust INTEGER,
    created TEXT,
    modified TEXT
);

-- -------------------------------------------------------------------------- --
--  /************************* Okay, initial inserts! *************************/
-- -------------------------------------------------------------------------- --
/*
    Our mirror, which is the only canonical one for now:
    https://asgard.paragonie.com/api
*/
INSERT INTO mirrors (
    url,
    created,
    modified
) VALUES (
    'https://asgard.paragonie.com/api',
    '2015-04-02T00:00:00',
    '2015-04-02T00:00:00'
);

/*
    Some initial notaries.
*/
INSERT INTO notaries (
    nickname,
    publickey,
    host,
    port,
    https,
    trust,
    created,
    modified
) VALUES (
    'paragon',
    '',
    'notary1.paragonie.com',
    38073,
    1,
    0,
    '2015-04-02T00:00:00',
    '2015-04-02T00:00:00'
);
