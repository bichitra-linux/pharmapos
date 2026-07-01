# Product

## Register

product

## Users

Pharmacists, pharmacy owners, and pharmacy staff operating retail pharmacies across Nepal. They manage daily dispensing, inventory, purchases from distributors, regulatory compliance (narcotics register, drug scheduling), and multi-outlet operations. Their context: standing at a counter, serving patients, scanning barcodes, checking prescriptions, processing payments via eSewa/Khalti/cash. Speed and accuracy are life-or-death; a wrong drug or dosage has real consequences.

## Product Purpose

PharmaPOS is a cloud-based, multi-tenant pharmacy Point-of-Sale and inventory management system built specifically for the Nepali pharmaceutical retail market. It replaces manual registers and generic billing software with a purpose-built tool that understands drug scheduling (H, H1, X, OTC), batch-wise expiry tracking, prescription management, and Nepal's regulatory requirements (PAN/VAT invoicing, narcotics register, DDA compliance).

Success means: a pharmacist can dispense a prescription in under 60 seconds, never sell an expired drug, never run out of a critical medicine without warning, and file government reports without leaving the system.

## Brand Personality

**Professional, efficient, trustworthy.** The interface should feel like a well-organized pharmacy shelf: everything labeled, everything in its place, nothing decorative that gets in the way of finding the right medicine fast. Think Stripe's clarity applied to pharmaceutical dispensing. The tool should disappear into the task.

## Anti-references

- **Consumer app aesthetics**: No playful cartoons, rounded bubbly shapes, gamification, or consumer-app onboarding flows. This is a professional tool used in a healthcare context.
- **Generic healthcare UI**: No white-and-teal cliché, no stock photo smiles, no overly clinical sterile feel. The system should feel modern and capable, not hospital-grade.
- **Dark-mode SaaS clichés**: No purple gradients, neon accents, glassmorphism, or crypto-aesthetic. The default is light mode; dark mode is a utility option, not a design statement.
- **AI-generated dashboards**: No identical card grids with icon + big number + small label. No gradient text. No hero-metric templates.

## Design Principles

1. **Speed is safety.** Every interaction optimized for the counter: barcode scan to dispensed medicine in minimum steps. A slow interface in a pharmacy means patients wait and errors increase.

2. **Clarity over decoration.** Information density is a feature, not a problem. Pharmacists need to see batch numbers, expiry dates, stock levels, and prices simultaneously. Don't hide data behind progressive disclosure for aesthetic reasons.

3. **Trustworthy precision.** Numbers must be exact. Currency formatted correctly (NPR with lakh/crore notation). Dates shown in both AD and Bikram Sambat. No rounding surprises. The interface should feel as precise as a pharmacist's scale.

4. **Regulatory confidence.** The system handles controlled substances, narcotics registers, and government reporting. The interface should make compliance feel effortless, not burdensome. Clear indicators for scheduled drugs, mandatory prescription checks, and audit trails.

5. **Scale gracefully.** Works for a single-counter pharmacy and a 5-outlet chain. The same interface adapts through configuration, not code changes. Multi-tenant by design, white-label capable.

## Accessibility & Inclusion

WCAG AA compliance. Standard accessibility requirements:
- All interactive elements properly labeled for screen readers
- Keyboard navigation for all critical flows
- Sufficient color contrast (4.5:1 minimum for text)
- Focus indicators on all interactive elements
- No information conveyed by color alone
