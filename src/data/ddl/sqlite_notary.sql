BEGIN;
    /*
        The building blocks of our signature verification system
    */
    CREATE TABLE blocks (
        "id" INTEGER PRIMARY KEY ASC,
        "hash" TEXT,
        "prevhash" TEXT,
        "nexthash" TEXT,
        "tailhash" TEXT,
        "verified" INTEGER,
        "prevblock" INTEGER NULL,
        "nextblock" INTEGER NULL,
        "contents" TEXT
    );
    CREATE INDEX "blocks_hash_idx" ON blocks ("hash");
    CREATE INDEX "blocks_tailhash_idx" ON blocks ("tailhash");
    CREATE INDEX "blocks_prevhash_idx" ON blocks ("prevhash");
    CREATE INDEX "blocks_nexthash_idx" ON blocks ("nexthash");
    CREATE INDEX "blocks_prevblock_idx" ON blocks ("prevblock");
    CREATE INDEX "blocks_nextblock_idx" ON blocks ("nextblock");
COMMIT;