import Foundation

/// Matches OCR text against the current UK registration format only
/// (two letters, two digits, three letters - e.g. "AB12 CDE",
/// introduced September 2001). Covers the overwhelming majority of
/// vehicles in circulation; older prefix/suffix formats are a
/// deliberate v1 gap, not an oversight.
enum UKPlateMatcher {
    private static let pattern = try! NSRegularExpression(pattern: "^[A-Z]{2}[0-9]{2}[A-Z]{3}$")

    /// Returns the first OCR candidate that, once stripped of spaces and
    /// punctuation, matches the current plate format - or nil if none do.
    static func bestMatch(in candidates: [String]) -> String? {
        for raw in candidates {
            let normalised = raw.uppercased()
                .replacingOccurrences(of: "[^A-Z0-9]", with: "", options: .regularExpression)
            let range = NSRange(normalised.startIndex..., in: normalised)

            if pattern.firstMatch(in: normalised, range: range) != nil {
                return normalised
            }
        }

        return nil
    }
}
