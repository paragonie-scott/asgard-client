# I. ASGard Threat Model

ASGard solves the problem of [secure code delivery](https://defuse.ca/triangle-of-secure-code-delivery.htm).
First, we outline the attacks we aim to mitigate and then outline our defensive
strategies.

## Attacks

ASGard aims to provide authentic and secure software in hostile network environments.
We assume an attacker with the following capabilities:

1. Attackers can compromise web servers and replace packages with Trojan Horse
   malware. (*Common*)
2. Attackers have compromised one or more Certificate Authorities, which allows
   them to perform man-in-the-middle interception of HTTPS traffic. (*Rare*)
3. Attackers may be interested in targeted malware attacks or in infecting as
   many people as possible. (*Unknown*)
4. Attackers may be able to compromise the computers used by the developers of a
   given software package and issue a fake release. (*Unknown*)
5. Attackers may also compromise the signing key on the developer's computers 
   (if they even sign their packages to begin with). (*Unknown*)

**In all cases above, the hypothetical attackers are interested in compromising
developers through third-party source code.**

These are the capabilities traditionally assumed to be accessible only to nation
state-level cyber criminals. However, we must be prepared for the possibility
that any of the millions of hackers on the Internet could one day wield the same
capabilities.

## Defenses

### Deterministic Builds

The first line of defense against malicious third-party software is to be able
to reproduce the deliverables (binaries, tarballs, PHP Archives, etc.) exactly
from the source code.

Some benign differences (such as build timestamps) are permissible, but they 
should still be reported to the end user when they elect to install or upgrade a
package to the latest release.

ASGard goes a step further. We manually review all packages when we add them to
our repository, and we review every update to make sure there are no backdoors
being introduced.

### Cryptographic Signatures

In order to alleviate attacks between us and our users, all of our packages will
be cryptographically signed. The ASGard client will refuse to install any
package if there is not a valid signature. This ensures that, even in an HTTPS
failure, our users will receive a genuine copy of the requested software.

### Userbase Consistency Verification

As an added layer of security, we have designed the ASGard client to verifiably
guarantee that every package and signature you receive is the same set that
every other user receives. This prevents us from participating in a targeted
attack on your infrastructure against our will.

To mitigate this attack, we use a distributed ledger (similar to the blockchain
used in crypto-currencies, such as Bitcoin) and a system of notaries who will
confirm that their copy of the ledger is identical to the one you have. **Anyone
can run a notary server.**