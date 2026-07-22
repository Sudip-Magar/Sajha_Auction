# Three Named Auction Algorithms (with Mathematical Formulations)

Canonical, citable algorithms from auction theory for a **highest-bid auction** project. Each is a recognized, named mechanism with its founding reference, mathematical model, governing equations, and pseudocode.

**Common notation**

| Symbol | Meaning |
|--------|---------|
| $N=\{1,\dots,n\}$ | set of bidders |
| $b_i$ | bid of bidder $i$ |
| $v_i$ | private valuation of bidder $i$ |
| $b_{(k)}$ | the $k$-th highest bid (order statistic) |
| $F(\cdot),\,f(\cdot)$ | CDF and PDF of the valuation distribution |
| $r$ | reserve (minimum acceptable) price |

---

## Algorithm 1 — First-Price Sealed-Bid Auction (FPSB)

*Reference: the classical "highest-bid" mechanism; equilibrium analysis by Vickrey (1961).*

**Rule.** Each bidder submits one sealed bid. The highest bid wins and **pays its own bid**.

$$
w=\arg\max_{i\in N} b_i,\qquad p=b_w=b_{(1)}.
$$

**Equilibrium bidding strategy.** With $n$ bidders whose values are i.i.d. from $F$ on $[0,\bar v]$, the symmetric Bayes–Nash equilibrium bid is

$$
\boxed{\;\beta(v)=v-\frac{1}{F(v)^{\,n-1}}\int_{0}^{v}F(x)^{\,n-1}\,dx\;}
$$

For uniform values $v\sim\mathrm{Unif}[0,\bar v]$ this reduces to the closed form

$$
\beta(v)=\frac{n-1}{n}\,v.
$$

Bidders **shade** their bids below their true value; more competition ($n\uparrow$) shrinks the shading.

```
function FPSB(bids b[1..n]):
    w = argmax_i b[i]
    return (winner = w, price = b[w])
```

---

## Algorithm 2 — Vickrey Auction (Second-Price Sealed-Bid)

*Reference: Vickrey (1961). A special case of the VCG mechanism.*

**Rule.** Sealed bids; the highest bidder wins but **pays the second-highest bid**.

$$
w=\arg\max_{i\in N}b_i,\qquad p=b_{(2)}=\max_{i\neq w}b_i.
$$

**Key theorem (dominant-strategy truthfulness).** Bidding one's true value is a weakly dominant strategy:

$$
\beta(v_i)=v_i\quad\text{for every bidder } i.
$$

*Proof sketch.* A bidder's utility is $u_i=(v_i-b_{(2)})\,\mathbb{1}[i\text{ wins}]$. Because the price a winner pays does not depend on their own bid, no over- or under-bid can improve the outcome — raising the bid only risks winning at a loss, lowering it only risks forgoing a profitable win.

```
function VICKREY(bids b[1..n]):
    w  = argmax_i b[i]
    p  = max_{i != w} b[i]          # second-highest bid
    return (winner = w, price = p)
```

---

## Algorithm 3 — Myerson Optimal Auction

*Reference: Myerson (1981), "Optimal Auction Design." The revenue-maximizing mechanism.*

**Idea.** Rank bidders not by raw bid but by **virtual valuation**, and enforce a reserve so the seller maximizes expected revenue.

**Virtual valuation.**

$$
\psi(v)=v-\frac{1-F(v)}{f(v)}.
$$

**Allocation rule.** Award to the bidder with the highest *nonnegative* virtual value:

$$
w=\arg\max_{i:\,\psi(v_i)\ge 0}\ \psi(v_i);
\qquad\text{no sale if all } \psi(v_i)<0.
$$

**Optimal reserve price** $r^\ast$ solves $\psi(r^\ast)=v_0$, where $v_0$ is the seller's own value:

$$
\boxed{\;r^\ast-\frac{1-F(r^\ast)}{f(r^\ast)}=v_0\;}
$$

For uniform values $v\sim\mathrm{Unif}[0,1]$ with $v_0=0$: $\;r^\ast=\tfrac{1}{2}$.

**Expected revenue** (payments equal expected virtual value of the winner):

$$
\mathbb{E}[\text{Rev}]=\mathbb{E}\!\left[\max_i\ \psi(v_i)^{+}\right],\qquad x^{+}=\max(x,0).
$$

```
function MYERSON(values v[1..n], F, f, seller_value v0):
    r = solve  psi(r) = v0     where psi(x) = x - (1 - F(x))/f(x)
    C = { i : v[i] >= r }                       # clears reserve
    if C is empty: return (no_sale, price = 0)
    w = argmax_{i in C} psi(v[i])               # highest virtual value
    p = max( r , second-highest v among C )     # threshold payment
    return (winner = w, price = p)
```

---

## Optional — Bertsekas Auction Algorithm (computational)

*Reference: Bertsekas (1979). Solves the assignment problem via auction-style bidding — use this if your project is about matching bidders to items computationally rather than economic mechanism design.*

Persons bid for objects; each object $j$ carries a price $p_j$. Person $i$ picks the object maximizing net value $a_{ij}-p_j$, and raises its price by a **bid increment**:

$$
b_{ij}= a_{ij}-\max_{k\neq j}\{a_{ik}-p_k\}+\varepsilon.
$$

The algorithm terminates with an assignment within $n\varepsilon$ of optimal; **$\varepsilon$-scaling** drives it to exact optimality.

```
while some person i is unassigned:
    j*   = argmax_j ( a[i][j] - p[j] )              # best object
    gain = (a[i][j*] - p[j*]) - 2nd-best net value  # margin
    p[j*] = p[j*] + gain + epsilon                  # raise price
    assign i -> j*  (bump previous holder of j*)
```

---

## Quick reference

| # | Named algorithm | Winner pays | Bidder's optimal strategy |
|---|-----------------|-------------|----------------------------|
| 1 | First-Price Sealed-Bid (FPSB) | own (highest) bid | $\beta(v)=\frac{n-1}{n}v$ (shade below value) |
| 2 | Vickrey (Second-Price) | second-highest bid | $\beta(v)=v$ (bid truthfully) |
| 3 | Myerson Optimal | threshold / virtual value | reserve at $\psi(r^\ast)=v_0$ |
| + | Bertsekas Auction Algorithm | — (assignment) | maximize $a_{ij}-p_j$ |
