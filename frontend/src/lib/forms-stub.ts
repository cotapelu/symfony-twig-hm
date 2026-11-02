// $app/forms stub for testing
export function enhance(form: HTMLFormElement, options?: {
  submitter?: HTMLElement;
  result?: (response: Response) => Promise<void> | void;
}) {
  return {
    destroy: () => {}
  };
}