# Three Core Algorithms for a Highest-Bid Auction

This document specifies three self-contained algorithms for a **highest-bid (first-price) auction** system, where the item is sold to the bidder submitting the highest admissible bid. Each algorithm is given with its mathematical model, the governing equations, and pseudocode.

**Common notation**

| Symbol | Meaning |
|--------|---------|
| $N = \{1, 2, \dots, n\}$ | set of bidders |
| $b_i$ | bid submitted by bidder $i$ |
| $t_i$ | timestamp of bidder $i$'s bid |
| $r$ | reserve (minimum acceptable) price |
| $v_i$ | private valuation of bidder $i$ |
| $F(\cdot),\ f(\cdot)$ | CDF and PDF of the valuation distribution |
| $b_{(k)}$ | the $k$-th highest bid (order statistic) |

---

## Algorithm 1 — Winner Determination with Reserve Price and Tie-Breaking

**Goal.** Given all submitted bids, decide whether the item sells, to whom, and at what price.

### Mathematical model

Only bids that clear the reserve are *admissible*:

$$
V = \{\, i \in N : b_i \ge r \,\}.
$$

If $V = \varnothing$, the item is unsold. Otherwise the winner is the admissible bidder with the maximum bid:

$$
w = \arg\max_{i \in V} b_i .
$$

In a **first-price** auction the winner pays their own bid:

$$
p = b_w = \max_{i \in V} b_i = b_{(1)} .
$$

**Tie-breaking.** If several bidders share the top bid, define the tied set

$$
T = \{\, i \in V : b_i = b_{(1)} \,\},
$$

and select the earliest submission (smallest timestamp):

$$
w = \arg\min_{i \in T} t_i .
$$

This guarantees a deterministic, unique winner. (A uniform random draw $w \sim \mathrm{Unif}(T)$ is an alternative fair rule.)

### Pseudocode

```
function DETERMINE_WINNER(bids b[1..n], timestamps t[1..n], reserve r):
    V = { i : b[i] >= r }
    if V is empty:
        return (no_sale, price = 0)

    bmax = max_{i in V} b[i]
    T = { i in V : b[i] == bmax }         # tied top bidders
    w = argmin_{i in T} t[i]              # earliest bid wins ties
    return (winner = w, price = bmax)
```

**Complexity.** $O(n)$ time, single pass over the bids.

---

## Algorithm 2 — Ascending Price with Minimum Increment and Proxy Bidding

**Goal.** Drive the live "highest-bid" price upward safely. Enforce a minimum step between successive bids, and support *proxy bidding* where each bidder registers a secret maximum $m_i$ and the system bids on their behalf.

### Mathematical model

**Minimum increment.** Let the current standing price be $p$. The increment is a step function of the price band (common on live-auction platforms):

$$
\Delta(p) =
\begin{cases}
\Delta_1, & p < c_1 \\
\Delta_2, & c_1 \le p < c_2 \\
\ \vdots & \\
\Delta_K, & p \ge c_{K-1}
\end{cases}
$$

A new manual bid $b$ is **valid** only if

$$
b \ \ge\ p + \Delta(p).
$$

**Proxy bidding.** Suppose bidders register secret maximums and let

$$
m_{(1)} \ge m_{(2)} \ge \dots
$$

be these maximums in decreasing order. The system awards the item (provisionally) to the holder of $m_{(1)}$, but only raises the visible price high enough to beat the *second* highest maximum:

$$
p^{\ast} = \min\!\Big(\, m_{(1)},\ \; m_{(2)} + \Delta\big(m_{(2)}\big) \Big).
$$

The price never exceeds the leader's own maximum $m_{(1)}$, and it always stays at least one increment above the runner-up. If only one maximum exists, the price is simply the reserve:

$$
p^{\ast} = \max\!\big(r,\ \text{opening price}\big).
$$

### Pseudocode

```
function INCREMENT(p):                     # minimum step for current price
    for k in 1..K:
        if p < c[k]: return delta[k]
    return delta[K]

function NEXT_MIN_BID(p):
    return p + INCREMENT(p)

function PROXY_UPDATE(maxbids m[1..k], reserve r):
    sort m descending
    if k == 1:
        return (leader = holder(m[1]), price = max(r, opening))
    m1 = m[1]; m2 = m[2]
    price = min(m1, m2 + INCREMENT(m2))
    return (leader = holder(m1), price = price)
```

**Termination.** Because every accepted bid increases $p$ by at least $\Delta_{\min} = \min_k \Delta_k > 0$, and $p$ is bounded above by $\max_i m_i$, the number of price updates is at most

$$
\left\lceil \frac{\max_i m_i - p_0}{\Delta_{\min}} \right\rceil,
$$

so the auction is guaranteed to end in finitely many steps.

---

## Algorithm 3 — Optimal Reserve Price and Equilibrium Bidding

**Goal.** Two connected pieces of theory: (a) how a rational bidder *should* bid in a first-price auction, and (b) how the seller should set the reserve $r$ to maximize expected revenue.

### (a) Symmetric equilibrium bidding strategy

Assume $n$ bidders with valuations drawn independently from a common distribution $F$ on $[0, \bar v]$. In a first-price sealed-bid auction, the symmetric Bayes–Nash equilibrium bid for a bidder with value $v$ is

$$
\boxed{\,\beta(v) \;=\; v \;-\; \frac{1}{F(v)^{\,n-1}} \int_{0}^{v} F(x)^{\,n-1}\, dx\,}
$$

This says: **bid your value minus the expected "money left on the table."** More competition (larger $n$) shrinks the shading term, pushing bids toward true value.

**Closed form for the uniform case.** If $v \sim \mathrm{Unif}[0,\bar v]$, then $F(x) = x/\bar v$ and the integral collapses to

$$
\beta(v) \;=\; \frac{n-1}{n}\, v .
$$

For example, with $n = 4$ bidders, each bids $\tfrac{3}{4}$ of their private value.

### (b) Revenue-maximizing reserve price

Let the seller's own valuation of the item be $v_0$ (the value of keeping it). The optimal reserve $r^{\ast}$ is the solution of the **virtual-value** condition

$$
\boxed{\,r^{\ast} \;-\; \frac{1 - F(r^{\ast})}{f(r^{\ast})} \;=\; v_0\,}
$$

The left-hand side is the seller's *virtual valuation* $\psi(r) = r - \frac{1-F(r)}{f(r)}$; the optimum sets it equal to the outside option $v_0$.

**Closed form for the uniform case.** For $v \sim \mathrm{Unif}[0,1]$ we have $F(r)=r$, $f(r)=1$, so

$$
r^{\ast} - (1 - r^{\ast}) = v_0 \quad\Longrightarrow\quad r^{\ast} = \frac{1 + v_0}{2}.
$$

If the seller values the item at $v_0 = 0$, the optimal reserve is still $r^{\ast} = \tfrac{1}{2}$ — strictly positive, because a reserve extracts more surplus even at the cost of occasionally not selling.

**Expected revenue** under reserve $r$, using order statistics of $n$ i.i.d. draws, is

$$
\mathbb{E}[\text{Rev}] \;=\; \int_{r}^{\bar v} x \, dG_{(1)}(x) \;+\; v_0\, \Pr\!\big(b_{(1)} < r\big),
\qquad G_{(1)}(x) = F(x)^{\,n}.
$$

### Pseudocode

```
function EQUILIBRIUM_BID(v, n, F):
    # generic form; use closed form when F is known
    num = integral of F(x)^(n-1) dx  from 0 to v
    return v - num / (F(v)^(n-1))

function OPTIMAL_RESERVE(F, f, v0):
    # solve psi(r) = r - (1 - F(r))/f(r) = v0 for r
    solve_for r in:  r - (1 - F(r)) / f(r) = v0
    return r
```

---

## How the three fit together

The three algorithms map onto the three phases of a highest-bid auction: **Algorithm 3(a)** tells each bidder what to submit, the **ascending/proxy mechanics (Algorithm 2)** move the live price and shield the leader's maximum, and once bidding closes **Algorithm 1** applies the reserve from **Algorithm 3(b)** to pick the winner and the price. Together they cover strategy, price dynamics, and settlement for a complete highest-bid auction engine.
