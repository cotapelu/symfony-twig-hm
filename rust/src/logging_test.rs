use crate::logging;

#[test]
fn test_logging_init_does_not_panic() {
    // Calling init should not panic. Note: in a full test suite, it should be called only once.
    logging::init();
}
